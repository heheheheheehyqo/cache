<?php

namespace Hyqo\Cache\Client;

use Hyqo\Cache\CacheInterface;
use Hyqo\Cache\CacheItem;
use Hyqo\Cache\ClientInterface;
use Hyqo\Cache\Collection;

class FilesystemClient implements ClientInterface
{
    private $pool;

    /** @var int */
    private $lifetime;

    /** @var string */
    private $directory;

    public function __construct(
        CacheInterface $pool,
        ?string $namespace = null,
        int $lifetime = 0,
        ?string $directory = null
    ) {
        if ($directory === null) {
            $directory = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'hyqo-cache';
        } else {
            $directory = realpath($directory) ?: $directory;
        }

        if ($namespace === null) {
            $namespace = '@';
        }

        $directory .= \DIRECTORY_SEPARATOR . $namespace;

        if (!is_dir($directory) && !mkdir($directory, 0777, true)) {
            throw new \InvalidArgumentException(sprintf('Can\'t create directory (%s).', $directory));
        }

        $this->pool = $pool;
        $this->lifetime = $lifetime;
        $this->directory = $directory . \DIRECTORY_SEPARATOR;
    }

    private function getFile(string $key): string
    {
        return $this->directory . md5($key);
    }

    /** @inheritDoc */
    public function doFetch(array $keys, ?\Closure $handle = null): Collection
    {
        $collection = new Collection($this->pool);

        foreach ($keys as $key) {
            $file = $this->getFile($key);

            if (!file_exists($file)) {
                $cacheItem = new CacheItem($this->pool, $key, null, false);
            } else {
                [$expiresAt, $value] = $this->doRead($file);

                $cacheItem = new CacheItem($this->pool, $key, $value, !$expiresAt || $expiresAt >= time());
            }

            $cacheItem->setMeta('file', $file);

            if (!$cacheItem->isHit()) {
                $this->lifetime && $cacheItem->expiresAfter($this->lifetime);

                if (null !== $handle) {
                    $handle($cacheItem);
                }
            }

            $collection->add($cacheItem);
        }

        return $collection;
    }

    /** @param CacheItem[] $items */
    public function doSave(array $items): bool
    {
        $ok = true;

        foreach ($items as $item) {
            $file = $this->getFile($item->key());

            $tmp = $this->directory . uniqid('', true);

            $ok = (@file_put_contents($tmp, $item->getExpiresAt() . PHP_EOL . $item->get()) !== false) && $ok;

            $renameResult = @rename($tmp, $file);
            $ok = $renameResult && $ok;

            if ($renameResult === false) {
                @unlink($tmp);
            }
        }

        return $ok;
    }

    private function doRead(string $file): array
    {
        if (!$handle = @fopen($file, 'rb')) {
            throw new \InvalidArgumentException(sprintf('File (%s) is unreadable', $file));
        }

        $expiresAt = (int)fgets($handle);
        $value = stream_get_contents($handle);
        fclose($handle);

        return [$expiresAt, $value];
    }

    public function doDelete(array $keys): bool
    {
        $ok = true;

        foreach ($keys as $key) {
            $filename = $this->getFile($key);

            $ok = (!file_exists($filename) || @unlink($filename)) && $ok;
        }

        return $ok;
    }

    public function doFlush(): bool
    {
        $ok = true;
        foreach ($this->scan($this->directory) as $file) {
            if (is_dir($file)) {
                $ok = @rmdir($file) && $ok;
            } else {
                $ok = @unlink($file) && $ok;
            }
        }

        return $ok;
    }

    private function scan(string $directory): \Generator
    {
        $directory = rtrim($directory, \DIRECTORY_SEPARATOR);

        foreach (glob("{$directory}/*") as $file) {
            if (is_dir($file)) {
                yield from $this->scan($file);
            }

            yield $file;
        }
    }
}
