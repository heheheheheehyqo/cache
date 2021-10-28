<?php

namespace Hyqo\Cache\Layer;

use Closure;
use Hyqo\Cache\Cache;
use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Client\MemcachedPlaceholderClient;

class MemcachedLayer implements Cache
{
    /** @var \Memcached|MemcachedPlaceholderClient */
    private $client;

    private $expiresAfter;

    public function __construct(string $namespace = '@', int $expiresAfter = 0, string $address = 'memcached:11211')
    {
        if (!preg_match('/^(?P<host>[\w.]+):(?P<port>[\d]+)$/', $address, $matches)) {
            throw new \InvalidArgumentException('Address must be "ip:port"');
        }

        if (!class_exists('Memcached')) {
            throw new \RuntimeException('ext-memcached doesn\'t not exist');
        }

        $this->client = new \Memcached($namespace);

        $this->client->addServer($matches['host'], $matches['port']);

        $this->client->setOption(\Memcached::OPT_PREFIX_KEY, $namespace . '_');

        $this->expiresAfter = $expiresAfter;
    }

    public function getItem(string $key, ?Closure $computeValue = null): CacheItem
    {
        $data = $this->client->get($key, null, \Memcached::GET_EXTENDED);

        $code = $this->client->getResultCode();

        if ($code === \Memcached::RES_NOTFOUND) {
            $cacheItem = new CacheItem($this, $key, null, false);
            $cacheItem->setMeta('cas', $data['cas']);
        } elseif ($code === \Memcached::RES_SUCCESS) {
            $cacheItem = new CacheItem($this, $key, $data['value'], true);
        } else {
            throw new \RuntimeException(
                json_encode(getenv('MEMCACHED_HOST')) . json_encode($_ENV) . 'MemcachedLayer client error: ' . $this->client->getResultMessage()
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

    public function save(CacheItem $cacheItem): void
    {
        $this->client->set($cacheItem->getKey(), $cacheItem->get(), $cacheItem->getExpiry());
    }

    public function delete(string $key): void
    {
        $this->client->delete($key);
    }

    public function flush(): bool
    {
        return $this->client->flush();
    }

}
