<?php

namespace Hyqo\Cache\Test;

use Hyqo\Cache\CacheFactory;
use Hyqo\Cache\Layer\FilesystemLayer;
use Hyqo\Cache\Layer\MemcachedLayer;
use PHPUnit\Framework\TestCase;

class CacheFactoryTest extends TestCase
{
    public function test_chain()
    {
        $chain = CacheFactory::chain('foo', 99, 'memcached:11211', __DIR__ . '/../var');

        $reflection = new \ReflectionClass($chain);
        $poolsProperty = $reflection->getProperty('pools');
        $poolsProperty->setAccessible('true');
        $pools = $poolsProperty->getValue($chain);

        $memcachedPool = $pools[0];
        $filesystemPool = $pools[1];

        $this->assertInstanceOf(MemcachedLayer::class, $memcachedPool);
        $this->assertInstanceOf(FilesystemLayer::class, $filesystemPool);
    }
}
