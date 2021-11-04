<?php

namespace Hyqo\Cache\Layer;

use Hyqo\Cache\Cache;
use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Client;
use Hyqo\Cache\Client\FilesystemClient;
use Hyqo\Cache\Collection;

class FilesystemLayer implements Cache
{
    /** @var Client */
    private $client;

    public function __construct(?string $namespace = null, int $lifetime = 0, ?string $directory = null)
    {
        $this->client = new FilesystemClient($this, $namespace, $lifetime, $directory);
    }

    public function getItem(string $key, ?\Closure $computeValue = null): CacheItem
    {
        return $this->client->doFetch(
            [$key],
            $computeValue ?
                function (CacheItem $item) use ($computeValue) {
                    $value = $computeValue($item);
                    $item->set($value);
                } : null
        )[$key];
    }

    public function getItems(array $keys, ?\Closure $handle = null): Collection
    {
        return $this->client->doFetch($keys, $handle);
    }

    /** @param CacheItem[] $items */
    public function save(array $items): bool
    {
        return $this->client->doSave($items);
    }

    public function persist(): bool
    {
        return false;
    }

    public function deleteItem(string $key): bool
    {
        return $this->client->doDelete([$key]);
    }

    public function deleteItems(array $keys): bool
    {
        return $this->client->doDelete($keys);
    }

    public function flush(): bool
    {
        return $this->client->doFlush();
    }
}
