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

        $address = sprintf('%s:11211', $_ENV['MEMCACHED_HOST'] ?? 'memcached');

        return new MemcachedLayer($namespace, $expiresAfter, $address);
    }

    public function test_write()
    {
        $cache = $this->createMemcachedLayer('@');
        $cache->getItem('bar', function () {
            return 'foo';
        });
        $this->assertTrue($cache->getItem('bar')->isHit());
    }

    public function test_read()
    {
        $cache = $this->createMemcachedLayer('@');
        $cache->getItem('foo', function () {
            return 'bar';
        });

        $item = $cache->getItem('foo');

        $this->assertEquals($item->get(), 'bar');
    }

    public function test_delete()
    {
        $cache = $this->createMemcachedLayer('@');
        $item = $cache->getItem('key_for_delete', function () {
            return 'value_for_delete';
        });
        $item->delete();

        $this->assertFalse($cache->getItem('key_for_delete')->isHit());

        $another_item = $cache->getItem('another_key_for_delete', function () {
            return 'another_value_for_delete';
        });

        $cache->delete($another_item->getKey());

        $this->assertFalse($cache->getItem('another_key_for_delete')->isHit());
    }

    public function test_multiple()
    {
        $cache = $this->createMemcachedLayer('multiple');
        $amount = 10;

        for ($i = 1; $i <= $amount; $i++) {
            $cache->getItem('key_' . $i, function () {
                return 'bar';
            });
        }

        for ($i = 1; $i <= $amount; $i++) {
            $item = $cache->getItem('key_' . $i);
            $item->delete();

            $this->assertFalse($cache->getItem('key_' . $i)->isHit());
        }
    }

    public function test_expiry()
    {
        $cache = $this->createMemcachedLayer('expiry');

        for ($i = 1; $i <= 2; $i++) {
            $item = $cache->getItem('expiry', static function (CacheItem $cacheItem) use ($i) {
                $cacheItem->expiresAfter(-1);

                return 'i: ' . $i;
            });
        }

        $this->assertEquals('i: 2', $item->get());

        $cache = $this->createMemcachedLayer('expiry', 60);

        for ($i = 1; $i <= 2; $i++) {
            $item = $cache->getItem('expiry', static function () use ($i) {
                return 'i: ' . $i;
            });
        }

        $this->assertEquals('i: 1', $item->get());
    }

    public function test_flush()
    {
        $cache = $this->createMemcachedLayer('flush');

        for ($i = 1; $i <= random_int(5, 20); $i++) {
            $cache->getItem('key' . $i, static function () use ($i) {
                return $i;
            });
        }

        $this->assertTrue($cache->flush());
    }

}
