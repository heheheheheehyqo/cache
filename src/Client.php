<?php

namespace Hyqo\Cache;

interface Client
{
    /**
     * @param string[] $keys
     * @param \Closure|null $handle
     * @return Collection
     */
    public function doFetch(array $keys, ?\Closure $handle = null): Collection;

    /** @param CacheItem[] $items */
    public function doSave(array $items): bool;

    public function doDelete(array $keys): bool;

    public function doFlush(): bool;
}
