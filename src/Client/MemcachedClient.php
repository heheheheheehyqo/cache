<?php

namespace Hyqo\Cache\Client;

use Closure;
use Hyqo\Cache\CacheInterface;
use Hyqo\Cache\CacheItem;
use Hyqo\Cache\ClientInterface;
use Hyqo\Cache\Collection;

class MemcachedClient implements ClientInterface
{
    /** @var \Memcached */
    private $connection;

    private $pool;

    private $lifetime;

    /** @var CacheItem[] */
    private $lazyStorage = [];

    public function __construct(
        CacheInterface $pool,
        string $namespace = '@',
        int $lifetime = 0,
        string $address = 'memcached:11211'
    ) {
        if (!preg_match('/^(?P<host>[\w.]+):(?P<port>[\d]+)$/', $address, $matches)) {
            throw new \InvalidArgumentException('Address must be "ip:port"');
        }

        if (!class_exists('Memcached')) {
            throw new \RuntimeException('ext-memcached doesn\'t not exist');
        }

        $this->connection = new \Memcached(md5($address));

        if (!count($this->connection->getServerList())) {
            $this->connection->addServer($matches['host'], $matches['port']);
//            var_dump($namespace, $this->client->getServerList());
        }

        $this->connection->setOptions([
            \Memcached::OPT_PREFIX_KEY => $namespace . '_',
            \Memcached::OPT_NO_BLOCK => true,
            \Memcached::OPT_BINARY_PROTOCOL => true,
            \Memcached::OPT_TCP_NODELAY => true,
            \Memcached::OPT_LIBKETAMA_COMPATIBLE => true,
        ]);

        $this->pool = $pool;
        $this->lifetime = $lifetime;
    }

    public function doFetch(array $keys, ?\Closure $handle = null): Collection
    {
        $collection = new Collection($this->pool);

        $values = $this->connection->getMulti($keys, \Memcached::GET_EXTENDED);

        foreach ($values as $key => $data) {
            $collection->add($this->createItem($key, $data));
        }

        foreach (array_diff($keys, array_keys($values)) as $key) {
            $collection->add($this->createItem($key, null, $handle));
        }

        return $collection;
    }

    private function createItem(string $key, ?array $data, ?\Closure $handle = null)
    {
        if ($data === null) {
            $item = new CacheItem($this->pool, $key, null, false);
            $this->lifetime && $item->expiresAfter($this->lifetime);

            if (null !== $handle) {
                $handle($item);
            }
        } else {
            $item = new CacheItem($this->pool, $key, $data['value'], true);
            $item->setMeta('cas', $data['cas']);
        }

        return $item;
    }


    public function getItem(string $key, ?Closure $computeValue = null): CacheItem
    {
        $data = $this->connection->get($key, null, \Memcached::GET_EXTENDED);

        $code = $this->connection->getResultCode();

        if ($code === \Memcached::RES_SUCCESS) {
            $cacheItem = new CacheItem($this->pool, $key, $data['value'], true);
            $cacheItem->setMeta('cas', $data['cas']);
        } elseif ($code === \Memcached::RES_NOTFOUND) {
            $cacheItem = new CacheItem($this->pool, $key, null, false);
        } else {
            throw new \RuntimeException(
                sprintf(
                    'MemcachedLayer error: %d — %s',
                    $this->connection->getResultCode(),
                    $this->connection->getResultMessage()
                )
            );
        }

        $cacheItem->setMeta('code', $this->connection->getResultCode());

        if (!$cacheItem->isHit()) {
            $this->lifetime && $cacheItem->expiresAfter($this->lifetime);

            if (null !== $computeValue) {
                $value = $computeValue($cacheItem);
                $cacheItem->set($value);
            }
        }

        return $cacheItem;
    }

    /** @param CacheItem[] $items */
    public function doSave(array $items): bool
    {
        if (!$items) {
            return true;
        }

        $valuesByExpiration = [];

        foreach ($items as $item) {
            if ($item->getExpiresAfter() < 60 * 60 * 24 * 30) {
                $expiration = $item->getExpiresAfter();
            } else {
                $expiration = $item->getExpiresAt();
            }

            if (isset($valuesByExpiration[$expiration])) {
                $valuesByExpiration[$expiration][$item->key()] = $item->get();
            } else {
                $valuesByExpiration[$expiration] = [$item->key() => $item->get()];
            }
        }

        $ok = true;

        foreach ($valuesByExpiration as $expiration => $values) {
            $ok = $this->connection->setMulti($values, $expiration) && $ok;
        }

        return $ok;
    }

    public function doDelete(array $keys): bool
    {
        return count($keys) === count($this->connection->deleteMulti($keys));
    }

    public function doFlush(): bool
    {
        return $this->connection->flush();
    }
}
