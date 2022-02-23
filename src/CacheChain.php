<?php

namespace Hyqo\Cache;

class CacheChain implements CacheLayerInterface
{
    /** @var CacheLayerInterface[] */
    private $pools;

    /**
     * @param CacheLayerInterface[] $pools
     */
    public function __construct(array $pools)
    {
        if (!count($pools)) {
            throw new \RuntimeException('At least one cache layer must be provide');
        }

        $this->pools = $pools;
    }

    public function getItemCreatedAfter(int $createdAt, string $key, ?\Closure $handle = null): CacheItem
    {
        $generator = $this->itemGenerator($handle);

        /** @var CacheLayerInterface $pool */

        while ($generator->valid()) {
            $pool = $generator->current();
            $cacheItem = $pool->getItemCreatedAfter($createdAt, $key);
            $generator->send($cacheItem);
        }

        return $generator->getReturn();
    }

    public function getItem(string $key, ?\Closure $handle = null): CacheItem
    {
        $generator = $this->itemGenerator($handle);

        /** @var CacheLayerInterface $pool */

        while ($generator->valid()) {
            $pool = $generator->current();
            $cacheItem = $pool->getItem($key);
            $generator->send($cacheItem);
        }

        return $generator->getReturn();
    }

    protected function itemGenerator(?\Closure $handle = null): \Generator
    {
        foreach ($this->pools as $i => $pool) {
            /** @var CacheItem $cacheItem */
            $cacheItem = yield $pool;

            if ($cacheItem->isHit()) {
                while (--$i >= 0) {
                    $this->pools[$i]->save([self::copyCacheItem($cacheItem)]);
                }

                return $cacheItem;
            }
        }

        if (null !== $handle) {
            $value = $handle($cacheItem);
            $cacheItem->set($value);
        }

        return $cacheItem;
    }

    protected static function copyCacheItem(CacheItem $item): CacheItem
    {
        return (new CacheItem($item->pool(), $item->key(), $item->get(), false))
            ->setExpiresAt($item->getExpiresAt());
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

    public function delete(string $key): bool
    {
        $ok = false;

        foreach ($this->pools as $cache) {
            $ok = $cache->delete($key) || $ok;
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

    public function save(array $items): bool
    {
        foreach ($this->pools as $cache) {
            $cache->save($items);
        }

        return true;
    }
}
