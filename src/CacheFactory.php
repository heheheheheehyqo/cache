<?php

namespace Hyqo\Cache;

use Hyqo\Cache\Layer\FilesystemLayer;
use Hyqo\Cache\Layer\MemcachedLayer;

class CacheFactory
{
    public static function chain(
        string $namespace,
        int $lifetime,
        string $memcachedAddress,
        string $filesystemDirectory
    ) {
        return new CacheChain([
            new MemcachedLayer($namespace, $lifetime, $memcachedAddress),
            new FilesystemLayer($namespace, $lifetime, $filesystemDirectory)
        ]);
    }
}
