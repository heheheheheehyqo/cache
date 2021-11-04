<?php

namespace Hyqo\Cache\Chain;

use Hyqo\Cache\Cache;
use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Collection;

class CacheChain
{
    /** @var Cache[] */
    private $caches;

    /**
     * @param Cache[] $caches
     */
    public function __construct(array $caches)
    {
        $this->caches = $caches;
    }

    public function getItem(string $key, ?\Closure $computeValue = null): CacheItem
    {
        /** @var CacheItem[] $siblings */
        $siblings = [];

        foreach ($this->caches as $cache) {
            $cacheItem = $cache->getItem($key, $computeValue);

            if ($cacheItem->isHit()) {
                $cacheItem->setMeta('siblings', $siblings);

                return $cacheItem;
            }

            $siblings[] = $cacheItem;
        }

        $cacheItem = array_pop($siblings);
        $cacheItem->setMeta('siblings', $siblings);

        return $cacheItem;
    }

    public function getItems(array $keys): Collection
    {
        $remains = $keys;

        /** @var Collection[] $previous */
        $previous = [];

        foreach ($this->caches as $cache) {
            if (!$remains) {
                break;
            }

            $collection = $cache->getItems($remains);

            $remains = array_diff($remains, $collection->getKeys());

            foreach ($previous as $previousCollection) {
                $collection->copyTo($previousCollection);
            }

            $previous[] = $collection;
        }
    }

    public function persist(): void
    {
        foreach ($this->caches as $cache) {
            $cache->persist();
        }
    }

    public function deleteItem(string $key): bool
    {
        $ok = false;

        foreach ($this->caches as $cache) {
            $ok = $cache->deleteItem($key) || $ok;
        }

        return $ok;
    }

    public function deleteItems(array $keys): bool
    {
        $ok = false;

        foreach ($this->caches as $cache) {
            $ok = $cache->deleteItems($keys) || $ok;
        }

        return $ok;
    }

    public function flush(): bool
    {
        $ok = false;

        foreach ($this->caches as $cache) {
            $ok = $cache->flush() || $ok;
        }

        return $ok;
    }
}
