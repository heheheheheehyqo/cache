<?php

namespace Hyqo\Cache\Traits;

use Hyqo\Cache\CacheItem;

trait FilesystemTrait
{
    private $directory;

    private $expiresAfter = 0;

    public function __construct(?string $namespace = null, int $expiresAfter = 0, ?string $directory = null)
    {
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

        $this->expiresAfter = $expiresAfter;
        $this->directory = $directory . \DIRECTORY_SEPARATOR;
    }

    protected function getFile(string $key): string
    {
        return $this->directory . md5($key);
    }

    public function doFetch(string $key, ?\Closure $computeValue): CacheItem
    {
        $file = $this->getFile($key);

        if (!file_exists($file)) {
            $cacheItem = new CacheItem($this, $key, null, false);
        } else {
            [$expiresAt, $value] = $this->doRead($file);

            $cacheItem = new CacheItem($this, $key, $value, true);
            $cacheItem->expiresAt($expiresAt);
        }

        $cacheItem->setMeta('file', $file);

        if (!$cacheItem->isHit() || $cacheItem->isExpired()) {
            if ($this->expiresAfter) {
                $cacheItem->expiresAfter($this->expiresAfter);
            } else {
                $cacheItem->expiresAt(0);
            }

            if (null !== $computeValue) {
                $value = $computeValue($cacheItem);
                $cacheItem->set($value);
            }
        }

        return $cacheItem;
    }

    public function doSave(CacheItem $cacheItem): void
    {
        $file = $this->getFile($cacheItem->getKey());

        $tmp = $this->directory . uniqid('', true);

        @file_put_contents($tmp, $cacheItem->getExpiry() . PHP_EOL . $cacheItem->get());

        if (false === @rename($tmp, $file)) {
            @unlink($tmp);
        }
    }

    protected function doRead(string $file): array
    {
        if (!$handle = @fopen($file, 'rb')) {
            throw new \InvalidArgumentException(sprintf('File (%s) is unreadable', $file));
        }

        $expiresAt = (int)fgets($handle);
        $value = stream_get_contents($handle);
        fclose($handle);

        return [$expiresAt, $value];
    }

    public function doDelete(string $key)
    {
        $filename = $this->getFile($key);

        if (file_exists($filename)) {
            @unlink($filename);
        }
    }

    public function doFlush(): bool
    {
        $ok = true;
        foreach ($this->scan($this->directory) as $file) {
            if (is_dir($file)) {
                @rmdir($file);
            } else {
                $ok = @unlink($file) && $ok;
            }
        }

        return true;
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
