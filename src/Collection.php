<?php

namespace Hyqo\Cache;

class Collection
{
    /** @var Cache */
    private $cache;

    /** @var CacheItem[] */
    private $storage;

    public function __construct(Cache $cache, array $storage)
    {
        $this->cache = $cache;
        $this->storage = $storage;
    }

    public function get(string $key): CacheItem
    {
        if (!isset($this->storage[$key])) {
            $this->storage[$key] = new CacheItem($this->cache, $key, null, false);
        }

        return $this->storage[$key];
    }
}
