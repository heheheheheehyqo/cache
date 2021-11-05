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
        $pools = [];

        if (class_exists('Memcached')) {
            $pools[] = new MemcachedLayer($namespace, $lifetime, $memcachedAddress);
        }

        $pools[] = new FilesystemLayer($namespace, $lifetime, $filesystemDirectory);

        return new CacheChain($pools);
    }
}
