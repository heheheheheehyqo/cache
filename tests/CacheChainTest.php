<?php

namespace Hyqo\Cache\Test;

use Hyqo\Cache\CacheChain;
use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Layer\FilesystemLayer;
use Hyqo\Cache\Layer\MemcachedLayer;
use PHPUnit\Framework\TestCase;

class CacheChainTest extends TestCase
{
    private $pool = [];

    public function tearDown(): void
    {
        foreach ($this->pool as $chainData) {
            $chainData['chain']->flush();
        }
    }

    private function getNamespace(string $namespace)
    {
        return 'hyqo-cache-chain-test-' . $namespace;
    }

    private function createChain(string $namespace, int $expiresAfter = 0): array
    {
        $namespace = $this->getNamespace($namespace);

        $memcachedAddress = sprintf('%s:11211', getenv('MEMCACHED_HOST') ?: 'memcached');
        $cacheRoot = __DIR__ . '/../../var';

        $pool = [
            'memcached' => new MemcachedLayer($namespace, $expiresAfter, $memcachedAddress),
            'filesystem' => new FilesystemLayer($namespace, $expiresAfter, $cacheRoot),
            'filesystem-double' => new FilesystemLayer($namespace . '-double', $expiresAfter, $cacheRoot),
        ];

        $chainData = ['chain' => new CacheChain(array_values($pool)), 'pool' => $pool];
        $this->pool[] = $chainData;

        return $chainData;
    }

    public function test_empty()
    {
        $chain = $this->createChain('@');

        $chain['chain']->getItem('foo', function () {
            return 'bar';
        });

        $this->assertFalse($chain['pool']['memcached']->getItem('foo')->isHit());
        $this->assertFalse($chain['pool']['filesystem']->getItem('foo')->isHit());
        $this->assertTrue($chain['pool']['filesystem-double']->getItem('foo')->isHit());
    }

    public function test_top_layer_exists()
    {
        $chain = $this->createChain('@');

        $chain['pool']['memcached']->getItem('foo', function () {
            return 'bar';
        });

        $chain['chain']->getItem('foo');

        $this->assertTrue($chain['pool']['memcached']->getItem('foo')->isHit());
        $this->assertFalse($chain['pool']['filesystem']->getItem('foo')->isHit());
        $this->assertFalse($chain['pool']['filesystem-double']->getItem('foo')->isHit());
    }

    public function test_middle_layer_exists()
    {
        $chain = $this->createChain('@');

        $chain['pool']['filesystem']->getItem('foo', function () {
            return 'bar';
        });

        $chain['chain']->getItem('foo');

        $this->assertTrue($chain['pool']['memcached']->getItem('foo')->isHit());
        $this->assertTrue($chain['pool']['filesystem']->getItem('foo')->isHit());
        $this->assertFalse($chain['pool']['filesystem-double']->getItem('foo')->isHit());
    }

    public function test_last_layer_exists()
    {
        $chain = $this->createChain('@');

        $chain['pool']['filesystem-double']->getItem('foo', function () {
            return 'bar';
        });

        $chain['chain']->getItem('foo');

        $this->assertTrue($chain['pool']['memcached']->getItem('foo')->isHit());
        $this->assertTrue($chain['pool']['filesystem']->getItem('foo')->isHit());
        $this->assertTrue($chain['pool']['filesystem-double']->getItem('foo')->isHit());
    }

    public function test_exists()
    {
        $chain = $this->createChain('@');

        $chain['pool']['filesystem']->getItem('foo', function () {
            return 'bar';
        });

        $item = $chain['chain']->getItem('foo');

        $this->assertTrue($item->isHit());
        $this->assertEquals('bar', $item->get());
    }

    public function test_not_exists()
    {
        $chain = $this->createChain('@');

        $item = $chain['chain']->getItem('foo');

        $this->assertFalse($item->isHit());
    }

    public function test_persist()
    {
        $chain = $this->createChain('@');

        $amount = 10;
        $range = range(1, $amount);

        foreach ($range as $i) {
            $chain['pool']['memcached']->getItem($i, function (CacheItem $item) {
                $item->lazy();

                return 'bar';
            });
        }

        $chain['chain']->persist();

        $item = $chain['chain']->getItem($amount);

        $this->assertTrue($item->isHit());
        $this->assertInstanceOf(MemcachedLayer::class, $item->pool(), get_class($item->pool()));
    }

    public function test_delete()
    {
        $chain = $this->createChain('@');

        $amount = 10;
        $range = range(1, $amount);

        foreach ($range as $i) {
            $chain['pool']['filesystem-double']->getItem($i)->lazy()->set('bar');
        }

        $chain['chain']->deleteItems($range);

        $item = $chain['chain']->getItem('foo');

        $this->assertFalse($item->isHit());
        $this->assertInstanceOf(FilesystemLayer::class, $item->pool());
    }
}
