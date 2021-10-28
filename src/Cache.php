<?php

namespace Hyqo\Cache;

interface Cache
{
    public function getItem(string $key, ?\Closure $computeValue = null): CacheItem;

    public function getItems(array $keys): Collection;

    public function save(CacheItem $cacheItem): void;

    public function persist(): void;

    public function deleteItem(string $key): bool;

    public function deleteItems(array $keys): bool;

    public function flush(): bool;
}
