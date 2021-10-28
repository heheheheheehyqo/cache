<?php

namespace Hyqo\Cache\Test;

use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Layer\MemcachedLayer;
use PHPUnit\Framework\TestCase;

class MemcachedLayerTest extends TestCase
{
    private $keyPrefix = 'hyqo-cache-test-';

    public function tearDown(): void
    {
        foreach (['@', 'expiry', 'flush'] as $namespace) {
            (new MemcachedLayer($this->keyPrefix . $namespace))->flush();
        }
    }

    public function test_write()
    {
        $cache = new MemcachedLayer($this->keyPrefix . '@');
        $item = $cache->getItem('bar', function () {
            return 'foo';
        });
        $this->assertTrue($cache->getItem('bar')->isHit());
    }

    public function test_read()
    {
        $cache = new MemcachedLayer($this->keyPrefix . '@');
        $cache->getItem('foo', function () {
            return 'bar';
        });

        $item = $cache->getItem('foo');

        $this->assertEquals($item->get(), 'bar');
    }

    public function test_delete()
    {
        $cache = new MemcachedLayer($this->keyPrefix . '@');
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
        $cache = new MemcachedLayer('stress');
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
        $cache = new MemcachedLayer($this->keyPrefix . 'expiry');

        for ($i = 1; $i <= 2; $i++) {
            $item = $cache->getItem('expiry', static function (CacheItem $cacheItem) use ($i) {
                $cacheItem->expiresAfter(-1);

                return 'i: ' . $i;
            });
        }

        $this->assertEquals('i: 2', $item->get());

        $cache = new MemcachedLayer($this->keyPrefix . 'expiry', 60);

        for ($i = 1; $i <= 2; $i++) {
            $item = $cache->getItem('expiry', static function () use ($i) {
                return 'i: ' . $i;
            });
        }

        $this->assertEquals('i: 1', $item->get());
    }

    public function test_flush()
    {
        $cache = new MemcachedLayer($this->keyPrefix . 'flush');

        for ($i = 1; $i <= random_int(5, 20); $i++) {
            $cache->getItem('key' . $i, static function () use ($i) {
                return $i;
            });
        }

        $this->assertTrue($cache->flush());
    }

}
