<?php

namespace Hyqo\Cache\Test;

use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Layer\MemcachedLayer;
use PHPUnit\Framework\TestCase;

class MemcachedLayerTest extends TestCase
{
    private $pool = [];

    public function tearDown(): void
    {
        foreach ($this->pool as $namespace) {
            (new MemcachedLayer($namespace))->flush();
        }
    }

    private function createMemcachedLayer(string $namespace, int $expiresAfter = 0): MemcachedLayer
    {
        $namespace = 'hyqo-cache-test-' . $namespace;

        if (!in_array($namespace, $this->pool, true)) {
            $this->pool[] = $namespace;
        }

        $address = sprintf('%s:11211', getenv('MEMCACHED_HOST') ?: 'memcached');

        return new MemcachedLayer($namespace, $expiresAfter, $address);
    }

    public function test_write_read(): void
    {
        $cache = $this->createMemcachedLayer('@');
        $cache->getItem('foo', function () {
            return 'bar';
        });

        $item = $cache->getItem('foo');

        $this->assertEquals($item->get(), 'bar');
    }

    public function test_delete(): void
    {
        $cache = $this->createMemcachedLayer('@');
        $item = $cache->getItem('key_for_delete', function () {
            return 'value_for_delete';
        });
        $cache->delete($item->key());

        $this->assertFalse($cache->getItem('key_for_delete')->isHit());
    }

    public function test_expiry(): void
    {
        $cache = $this->createMemcachedLayer('expiry');

        $cache->getItem('expiry', static function (CacheItem $cacheItem) {
            $cacheItem->setExpiresAfter(1);

            return 'foo';
        });

        $this->assertTrue($cache->getItem('expiry')->isHit());

        sleep(1);
        $this->assertFalse($cache->getItem('expiry')->isHit());
    }

    public function test_expiry_namespace(): void
    {
        $cache = $this->createMemcachedLayer('expiry', 1);

        $cache->getItem('expiry', static function () {
            return 'foo';
        });

        $this->assertTrue($cache->getItem('expiry')->isHit());

        sleep(1);
        $this->assertFalse($cache->getItem('expiry')->isHit());
    }

    public function test_flush(): void
    {
        $cacheFoo = $this->createMemcachedLayer('flush_foo');
        $cacheBar = $this->createMemcachedLayer('flush_bar');

        for ($i = 1; $i <= random_int(5, 20); $i++) {
            $cacheFoo->getItem('key' . $i, static function () use ($i) {
                return $i;
            });

            $cacheBar->getItem('key' . $i, static function () use ($i) {
                return $i;
            });
        }

        $this->assertTrue($cacheFoo->flush());
        $this->assertFalse($cacheBar->getItem('key1')->isHit());
    }

}
