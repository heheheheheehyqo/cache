<?php

namespace Hyqo\Cache\Client;

use Hyqo\Cache\CacheItem;
use Hyqo\Cache\CacheClientInterface;
use Hyqo\Cache\CacheLayerInterface;
use Hyqo\Cache\Meta;

/** @internal */
class MemcachedClient implements CacheClientInterface
{
    /** @var \Memcached */
    private $connection;

    private $pool;

    private $lifetime;

    /** @var CacheItem[] */
    private $lazyStorage = [];

    public function __construct(
        CacheLayerInterface $pool,
        string $namespace = '@',
        int $lifetime = 0,
        string $address = '127.0.0.1:11211'
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

    public function doFetch(string $key, ?\Closure $handle, int $minCreationTime = null): CacheItem
    {
        $data = $this->connection->get($key, null, \Memcached::GET_EXTENDED);

        $code = $this->connection->getResultCode();

        if ($code === \Memcached::RES_SUCCESS) {
            [$meta, $value] = $this->doRead($data['value']);
            [$version, $createdAt, $expiresAt] = Meta::unpack($meta);

            $isHit = Meta::isHit($version, $createdAt, $expiresAt, $minCreationTime);

            $cacheItem = new CacheItem($this->pool, $key, $value, $isHit, $createdAt);
            $cacheItem->setExpiresAt($expiresAt);
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

        if (!$cacheItem->isHit()) {
            $this->lifetime && $cacheItem->setExpiresAfter($this->lifetime);

            if (null !== $handle) {
                $handle($cacheItem);
            }
        }

        return $cacheItem;
    }

    private function doRead(string $raw): array
    {
        $parts = explode("\n", $raw, 2);

        $meta = $parts[0];
        $value = $parts[1] ?? '';

        return [$meta, $value];
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
                $valuesByExpiration[$expiration][$item->key()] = $item->pack();
            } else {
                $valuesByExpiration[$expiration] = [$item->key() => $item->pack()];
            }
        }

        $ok = true;

        foreach ($valuesByExpiration as $expiration => $values) {
            $ok = $this->connection->setMulti($values, $expiration) && $ok;
        }

        return $ok;
    }

    public function doDelete(string $key): bool
    {
        return $this->connection->delete($key);
    }

    public function doFlush(): bool
    {
        return $this->connection->flush();
    }
}
