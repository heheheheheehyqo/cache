<?php

namespace Hyqo\Cache;

interface Cache
{
    public function getItem(string $key, ?\Closure $computeValue = null): CacheItem;

    public function save(CacheItem $cacheItem): void;

    public function delete(string $key): void;

    public function flush(): bool;
}
