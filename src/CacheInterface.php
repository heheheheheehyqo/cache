<?php

namespace Hyqo\Cache;

interface CacheInterface
{
    public function getItem(string $key, ?\Closure $computeValue = null): CacheItem;

    public function getItems(array $keys): Collection;

    public function deleteItem(string $key): bool;

    public function deleteItems(array $keys): bool;

    /** @param CacheItem[] $items */
    public function save(array $items): bool;

    public function flush(): bool;
}
