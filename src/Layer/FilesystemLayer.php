<?php

namespace Hyqo\Cache\Layer;

use Hyqo\Cache\Cache;
use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Traits\FilesystemTrait;

class FilesystemLayer implements Cache
{
    use FilesystemTrait;

    public function getItem(string $key, ?\Closure $computeValue = null): CacheItem
    {
        return $this->doFetch($key, $computeValue);
    }

    public function save(CacheItem $cacheItem): void
    {
        $this->doSave($cacheItem);
    }

    public function delete(string $key): void
    {
        $this->doDelete($key);
    }

    public function flush(): bool
    {
        return $this->doFlush();
    }
}
