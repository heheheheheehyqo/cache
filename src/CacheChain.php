<?php

namespace Hyqo\Cache;

class CacheChain
{
    /** @var CacheInterface[] */
    private $pools;

    /**
     * @param CacheInterface[] $pools
     */
    public function __construct(array $pools)
    {
        if (!count($pools)) {
            throw new \RuntimeException('At least one cache layer must be provide');
        }

        $this->pools = $pools;
    }

    public function getItem(string $key, ?\Closure $computeValue = null): CacheItem
    {
        $copyItem = static function (CacheItem $item): CacheItem {
            return (new CacheItem($item->pool(), $item->key(), $item->get(), false))
                ->expiresAt($item->getExpiresAt());
        };

        foreach ($this->pools as $i => $pool) {
            $item = $pool->getItem($key);

            if ($item->isHit()) {
                while (--$i >= 0) {
                    $this->pools[$i]->save([$copyItem($item)]);
                }

                return $item;
            }
        }

        if ($computeValue) {
            $item->set($computeValue($item));
        }

        return $item;
    }

    public function persist(): bool
    {
        foreach ($this->pools as $cache) {
            if ($cache instanceof LazieableInterface) {
                $cache->persist();
            }
        }

        return true;
    }

    public function deleteItem(string $key): bool
    {
        $ok = false;

        foreach ($this->pools as $cache) {
            $ok = $cache->deleteItem($key) || $ok;
        }

        return $ok;
    }

    public function deleteItems(array $keys): bool
    {
        $ok = false;

        foreach ($this->pools as $cache) {
            $ok = $cache->deleteItems($keys) || $ok;
        }

        return $ok;
    }

    public function flush(): bool
    {
        $ok = false;

        foreach ($this->pools as $cache) {
            $ok = $cache->flush() || $ok;
        }

        return $ok;
    }
}
