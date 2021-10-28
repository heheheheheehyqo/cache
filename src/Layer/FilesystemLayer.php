<?php

namespace Hyqo\Cache\Layer;

use Hyqo\Cache\Cache;
use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Collection;
use Hyqo\Cache\Traits\FilesystemTrait;

class FilesystemLayer implements Cache
{
    use FilesystemTrait;

    public function getItem(string $key, ?\Closure $computeValue = null): CacheItem
    {
        return $this->doFetch($key, $computeValue);
    }

    public function getItems(array $keys): Collection
    {
        return $this->doFetchMulti($keys);
    }

    public function save(CacheItem $cacheItem): void
    {
        $this->doSave($cacheItem);
    }

    public function persist(): void
    {
    }

    public function deleteItem(string $key): bool
    {
        return $this->doDeleteItem($key);
    }

    public function deleteItems(array $keys): bool
    {
        return $this->doDeleteItems($keys);
    }

    public function flush(): bool
    {
        return $this->doFlush();
    }
}
