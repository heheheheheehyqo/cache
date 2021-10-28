<?php

namespace Hyqo\Cache\Layer;

use Closure;
use Hyqo\Cache\Cache;
use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Client\MemcachedPlaceholderClient;
use Hyqo\Cache\Collection;

class MemcachedLayer implements Cache
{
    /** @var \Memcached|MemcachedPlaceholderClient */
    private $client;

    private $expiresAfter;

    /** @var CacheItem[] */
    private $lazyStorage = [];

    public function __construct(string $namespace = '@', int $expiresAfter = 0, string $address = 'memcached:11211')
    {
        if (!preg_match('/^(?P<host>[\w.]+):(?P<port>[\d]+)$/', $address, $matches)) {
            throw new \InvalidArgumentException('Address must be "ip:port"');
        }

        if (!class_exists('Memcached')) {
            throw new \RuntimeException('ext-memcached doesn\'t not exist');
        }

        $this->client = new \Memcached(md5($address));

        if (!count($this->client->getServerList())) {
            $this->client->addServer($matches['host'], $matches['port']);
//            var_dump($namespace, $this->client->getServerList());
        }


        $this->client->setOptions([
            \Memcached::OPT_PREFIX_KEY => $namespace . '_',
            \Memcached::OPT_NO_BLOCK => true,
            \Memcached::OPT_BINARY_PROTOCOL => true,
            \Memcached::OPT_TCP_NODELAY => true,
            \Memcached::OPT_LIBKETAMA_COMPATIBLE => true,
        ]);

        $this->expiresAfter = $expiresAfter;
    }

    public function getItem(string $key, ?Closure $computeValue = null): CacheItem
    {
        $value = $this->client->get($key);

        $code = $this->client->getResultCode();

        if ($code === \Memcached::RES_NOTFOUND) {
            $cacheItem = new CacheItem($this, $key, null, false);
        } elseif ($code === \Memcached::RES_SUCCESS) {
            $cacheItem = new CacheItem($this, $key, $value, true);
        } else {
            throw new \RuntimeException(
                sprintf(
                    'MemcachedLayer error: %d — %s',
                    $this->client->getResultCode(),
                    $this->client->getResultMessage()
                )
            );
        }

        $cacheItem->setMeta('code', $this->client->getResultCode());

        if (!$cacheItem->isHit()) {
            if ($this->expiresAfter) {
                $cacheItem->expiresAfter($this->expiresAfter);
            } else {
                $cacheItem->expiresAt(0);
            }

            if (null !== $computeValue) {
                $value = $computeValue($cacheItem);
                $cacheItem->set($value);
            }
        }

        return $cacheItem;
    }

    public function getItems(array $keys): Collection
    {
        $items = [];

        foreach ($this->client->getMulti($keys) as $key => $value) {
            $items[] = new CacheItem($this, $key, $value, true);
        }

        return new Collection($this, $items);
    }

    public function save(CacheItem $cacheItem): void
    {
        if ($cacheItem->isLazy()) {
            $this->lazyStorage[] = $cacheItem;
        } else {
            $this->client->set($cacheItem->getKey(), $cacheItem->get(), $cacheItem->getExpiry());
        }
    }

    public function persist(): void
    {
        if (!count($this->lazyStorage)) {
            return;
        }

        $pairs = [];
        foreach ($this->lazyStorage as $cacheItem) {
            $pairs[$cacheItem->getKey()] = $cacheItem->get();
        }

        $this->client->setMulti($pairs, $this->expiresAfter ? (time() + $this->expiresAfter) : $this->expiresAfter);

        $this->lazyStorage = [];
    }

    public function deleteItem(string $key): bool
    {
        return $this->client->delete($key);
    }

    public function deleteItems(array $keys): bool
    {
        return count($keys) === count($this->client->deleteMulti($keys));
    }

    public function flush(): bool
    {
        return $this->client->flush();
    }

}
