<?php

namespace Hyqo\Cache;

interface Cache
{
    public function getItem(string $key, ?\Closure $computeValue = null): CacheItem;

    public function getItems(array $keys): Collection;

    /** @param CacheItem[] $items */
    public function save(array $items): bool;

    public function persist(): bool;

    public function deleteItem(string $key): bool;

    public function deleteItems(array $keys): bool;

    public function flush(): bool;
}
