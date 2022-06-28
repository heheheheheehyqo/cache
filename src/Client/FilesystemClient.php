<?php

namespace Hyqo\Cache\Client;

use Hyqo\Cache\CacheItem;
use Hyqo\Cache\CacheClientInterface;
use Hyqo\Cache\CacheLayerInterface;
use Hyqo\Cache\Meta;

/** @internal */
class FilesystemClient implements CacheClientInterface
{
    private $pool;

    /** @var int */
    private $lifetime;

    /** @var string */
    private $directory;

    public function __construct(
        CacheLayerInterface $pool,
        ?string $namespace = null,
        int $lifetime = 0,
        ?string $directory = null
    ) {
        if ($directory === null) {
            $directory = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'hyqo-cache';
        } else {
            $directory = (string)realpath($directory) ?: $directory;
        }

        if ($namespace === null) {
            $namespace = '@';
        }

        $directory .= \DIRECTORY_SEPARATOR . $namespace;

        $oldUmask = umask(0);

        /** @noinspection MkdirRaceConditionInspection */
        if (!is_dir($directory) && !mkdir($directory, 0777, true)) {
            throw new \InvalidArgumentException(sprintf('Can\'t create directory (%s).', $directory));
        }

        umask($oldUmask);

        $this->pool = $pool;
        $this->lifetime = $lifetime;
        $this->directory = $directory . \DIRECTORY_SEPARATOR;
    }

    private function getFile(string $key): string
    {
        return $this->directory . md5($key);
    }

    public function doFetch(string $key, ?\Closure $handle, int $minCreationTime = null): CacheItem
    {
        $file = $this->getFile($key);

        if (!file_exists($file)) {
            $cacheItem = new CacheItem($this->pool, $key, null, false);
        } else {
            [$meta, $value] = $this->doRead($file);
            [$version, $createdAt, $expiresAt] = Meta::unpack($meta);

            $isHit = Meta::isHit($version, $createdAt, $expiresAt, $minCreationTime);

            $cacheItem = new CacheItem($this->pool, $key, $value, $isHit, $createdAt);
            $cacheItem->setExpiresAt($expiresAt);
            $cacheItem->setMeta('file', $file);
        }

        if (!$cacheItem->isHit()) {
            $this->lifetime && $cacheItem->setExpiresAfter($this->lifetime);

            if (null !== $handle) {
                $handle($cacheItem);
            }
        }

        return $cacheItem;
    }

    /** @param CacheItem[] $items */
    public function doSave(array $items): bool
    {
        $ok = true;

        foreach ($items as $cacheItem) {
            $file = $this->getFile($cacheItem->key());

            $ok = (@file_put_contents(
                        $tmp = $this->directory . uniqid('', true),
                        $cacheItem->pack()
                    ) !== false) && $ok;

            $cacheItem->setMeta('file', $file);

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
        if (!$res = @fopen($file, 'rb')) {
            throw new \InvalidArgumentException(sprintf('File (%s) is unreadable', $file));
        }

        $meta = fgets($res);
        $value = stream_get_contents($res);
        fclose($res);

        return [$meta, $value];
    }

    public function doDelete(string $key): bool
    {
        $filename = $this->getFile($key);

        return (!file_exists($filename) || @unlink($filename));
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
