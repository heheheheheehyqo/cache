<?php

namespace Hyqo\Cache\Layer;

use Hyqo\Cache\CacheItem;
use Hyqo\Cache\CacheLayerInterface;
use Hyqo\Cache\CacheClientInterface;

abstract class CacheLayer implements CacheLayerInterface
{
    /** @var CacheClientInterface */
    protected $client;

    public function getItem(string $key, ?\Closure $handle = null): CacheItem
    {
        return $this->client->doFetch(
            $key,
            $handle ?
                static function (CacheItem $item) use ($handle) {
                    $value = $handle($item);
                    $item->set($value);
                } : null
        );
    }

    public function getItemCreatedAfter(int $createdAt, string $key, ?\Closure $handle = null): CacheItem
    {
        return $this->client->doFetch(
            $key,
            $handle ?
                static function (CacheItem $item) use ($handle) {
                    $value = $handle($item);
                    $item->set($value);
                } : null,
            $createdAt
        );
    }

    public function delete(string $key): bool
    {
        return $this->client->doDelete($key);
        return true;
    }

    public function flush(): bool
    {
        return $this->client->doFlush();
        return true;
    }
}
