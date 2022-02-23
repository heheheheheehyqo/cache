<?php

namespace Hyqo\Cache;

interface CacheClientInterface
{
    public function doFetch(string $key, ?\Closure $handle, int $timestamp = null): CacheItem;

    /** @param CacheItem[] $items */
    public function doSave(array $items): bool;

    public function doDelete(string $key): bool;

    public function doFlush(): bool;
}
