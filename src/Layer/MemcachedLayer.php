<?php

namespace Hyqo\Cache\Layer;

use Closure;
use Hyqo\Cache\Cache;
use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Client\MemcachedClient;
use Hyqo\Cache\Collection;

class MemcachedLayer implements Cache, Lazieable
{
    /** @var MemcachedClient */
    private $client;

    /** @var CacheItem[] */
    private $lazyStorage = [];

    public function __construct(string $namespace = '@', int $lifetime = 0, string $address = 'memcached:11211')
    {
        $this->client = new MemcachedClient($this, $namespace, $lifetime, $address);
    }

    public function getItem(string $key, ?Closure $computeValue = null): CacheItem
    {
        return $this->client->doFetch(
            [$key],
            $computeValue ?
                function (CacheItem $item) use ($computeValue) {
                    $value = $computeValue($item);
                    $item->set($value);
                } : null
        )[$key];
    }

    public function getItems(array $keys, ?\Closure $handle = null): Collection
    {
        return $this->client->doFetch($keys, $handle);
    }

    /** @param CacheItem[] $items */
    public function save(array $items): bool
    {
        $willBeSaved = [];

        foreach ($items as $item) {
            if ($item->isLazy()) {
                $this->lazyStorage[] = $item;
                continue;
            }

            $willBeSaved[] = $item;
        }

        return $this->client->doSave($willBeSaved);
    }

    public function persist(): bool
    {
        if (!count($this->lazyStorage)) {
            return false;
        }

        $willBeSaved = $this->lazyStorage;

        $this->lazyStorage = [];
        return $this->client->doSave($willBeSaved);
    }

    public function deleteItem(string $key): bool
    {
        return $this->client->doDelete([$key]);
    }

    public function deleteItems(array $keys): bool
    {
        return $this->client->doDelete($keys);
    }

    public function flush(): bool
    {
        return $this->client->doFlush();
    }

}
