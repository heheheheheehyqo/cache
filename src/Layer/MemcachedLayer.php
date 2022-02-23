<?php

namespace Hyqo\Cache\Layer;

use Closure;
use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Client\MemcachedClient;
use Hyqo\Cache\LazieableInterface;

class MemcachedLayer extends CacheLayer implements LazieableInterface
{
    /** @var CacheItem[] */
    private $lazyStorage = [];

    public function __construct(string $namespace = '@', int $lifetime = 0, string $address = '127.0.0.1:11211')
    {
        $this->client = new MemcachedClient($this, $namespace, $lifetime, $address);
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
}
