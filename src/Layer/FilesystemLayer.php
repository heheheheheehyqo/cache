<?php

namespace Hyqo\Cache\Layer;

use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Client\FilesystemClient;

class FilesystemLayer extends CacheLayer
{
    public function __construct(?string $namespace = null, int $lifetime = 0, ?string $directory = null)
    {
        $this->client = new FilesystemClient($this, $namespace, $lifetime, $directory);
    }

    /** @param CacheItem[] $items */
    public function save(array $items): bool
    {
        return $this->client->doSave($items);
    }
}
