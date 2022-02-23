<?php

namespace Hyqo\Cache;

interface CacheLayerInterface
{
    public function getItem(string $key, ?\Closure $handle = null): CacheItem;

    public function getItemCreatedAfter(int $createdAt, string $key, ?\Closure $handle = null): CacheItem;

    public function delete(string $key): bool;

    /** @param CacheItem[] $items */
    public function save(array $items): bool;

    public function flush(): bool;
}
