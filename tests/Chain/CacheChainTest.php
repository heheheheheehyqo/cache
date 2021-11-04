<?php

namespace Hyqo\Cache\Test\Chain;

use Hyqo\Cache\Chain\CacheChain;
use Hyqo\Cache\Layer\FilesystemLayer;
use Hyqo\Cache\Layer\MemcachedLayer;
use PHPUnit\Framework\TestCase;

class CacheChainTest extends TestCase
{
    private function getNamespace(string $namespace)
    {
        return 'hyqo-cache-chain-test-' . $namespace;
    }

    private function createChain(string $namespace, int $expiresAfter = 0): array
    {
        $namespace = $this->getNamespace($namespace);

        $memcachedAddress = sprintf('%s:11211', getenv('MEMCACHED_HOST') ?: 'memcached');
        $cacheRoot = __DIR__ . '/../../var-chain';

        $pool = [
            'memcached' => new MemcachedLayer($namespace, $expiresAfter, $memcachedAddress),
            'filesystem' => new FilesystemLayer($namespace, $expiresAfter, $cacheRoot),
            'filesystem-double' => new FilesystemLayer($namespace . '-double', $expiresAfter, $cacheRoot),
        ];

        return ['chain' => new CacheChain(array_values($pool)), 'pool' => $pool];
    }

    public function test_write()
    {
        $namespace = '@';
        $chain = $this->createChain($namespace);

        $chain['chain']->getItem('foo', function () {
            return 'bar';
        });

        $this->assertTrue($chain['pool']['memcached']->getItem('foo')->isHit());
        $this->assertTrue($chain['pool']['filesystem']->getItem('foo')->isHit());
        $this->assertTrue($chain['pool']['filesystem-double']->getItem('foo')->isHit());
    }
}
