<?php

namespace Hyqo\Cache;

class Collection implements \ArrayAccess
{
    /** @var Cache */
    private $pool;

    /** @var CacheItem[] */
    private $storage;

    public function __construct(Cache $pool, array $storage = [])
    {
        $this->pool = $pool;
        $this->storage = $storage;
    }

    public function getPool(): Cache
    {
        return $this->pool;
    }

    public function getItem(string $key): CacheItem
    {
        if (!isset($this->storage[$key])) {
            $this->storage[$key] = new CacheItem($this->pool, $key, null, false);
        }

        return $this->storage[$key];
    }

    public function add(CacheItem $item): CacheItem
    {
        return $this->storage[$item->getKey()] = $item;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->storage[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->storage[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            throw new \RuntimeException('Access to Collection\'s Item available only by key');
        } else {
            $this->storage[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->storage[$offset]);
    }


    /** @return CacheItem[] */
    public function getAll(): array
    {
        return $this->storage;
    }

    public function getKeys(): array
    {
        return array_keys($this->storage);
    }

    public function copyTo(Collection $collection): void
    {
        $cache = $collection->getPool();

        foreach ($this->storage as $cacheItem) {
            $collection->add($cacheItem)->lazy()->save();
        }

        $cache->persist();
    }

    public function getPairs(): array
    {
        return $this->storage;
    }
}
