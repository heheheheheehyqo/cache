<?php

namespace Hyqo\Cache\Test;

use Hyqo\Cache\CacheItem;
use Hyqo\Cache\Layer\FilesystemLayer;
use PHPUnit\Framework\TestCase;

class FilesystemLayerTest extends TestCase
{
    private $cacheRoot = __DIR__ . '/../var';

    public function tearDown(): void
    {
        if (is_dir($this->cacheRoot)) {
            foreach ($this->scan($this->cacheRoot) as $file) {
                if (is_dir($file)) {
                    @rmdir($file);
                } else {
                    @unlink($file);
                }
            }

            @rmdir($this->cacheRoot);
        }
    }

    private function scan(string $directory): \Generator
    {
        foreach (glob("{$directory}/*") as $file) {
            if (is_dir($file)) {
                yield from $this->scan($file);
            }

            yield $file;
        }
    }

    public function test_create_namespace_dir()
    {
        foreach (['@', 'test'] as $namespace) {
            new FilesystemLayer($namespace, 0, $this->cacheRoot);

            $this->assertDirectoryExists($this->cacheRoot . DIRECTORY_SEPARATOR, $namespace);
        }
    }

    public function test_write()
    {
        $cache = new FilesystemLayer('@', 0, $this->cacheRoot);
        $item = $cache->getItem('bar', function () {
            return 'foo';
        });
        $this->assertStringEqualsFile($item->getMeta('file'), '0' . PHP_EOL . 'foo');
    }

    public function test_read()
    {
        $cache = new FilesystemLayer('@', 0, $this->cacheRoot);
        $cache->getItem('foo', function () {
            return 'bar';
        });

        $item = $cache->getItem('foo');

        $this->assertEquals($item->get(), 'bar');
    }

    public function test_delete()
    {
        $cache = new FilesystemLayer('@', 0, $this->cacheRoot);
        $item = $cache->getItem('key_for_delete', function () {
            return 'value_for_delete';
        });
        $cache->deleteItem($item->key());

        $this->assertFileNotExists($item->getMeta('file'), $item->key());
    }

    public function test_multiple()
    {
        $cache = new FilesystemLayer('multiple', 0, $this->cacheRoot);
        $amount = 10;
        $range = range(1, $amount);

        $collection = $cache->getItems($range);

        foreach ($range as $i) {
            $collection->getItem($i)->lazy()->set('bar');
        }

        $this->assertTrue($cache->getItem($amount)->isHit());

        $cache->deleteItems($range);

        $this->assertFalse($cache->getItem($amount)->isHit());
    }

    public function test_expiry_item()
    {
        $cache = new FilesystemLayer('expiry', 0, $this->cacheRoot);

        for ($i = 1; $i <= 2; $i++) {
            $item = $cache->getItem('expiry', static function (CacheItem $cacheItem) use ($i) {
                $cacheItem->expiresAfter(-1);

                return 'i: ' . $i;
            });
        }

        $this->assertEquals('i: 2', $item->get());


        for ($i = 1; $i <= 2; $i++) {
            $item = $cache->getItem('expiry', static function (CacheItem $cacheItem) use ($i) {
                $cacheItem->expiresAfter(1);

                return 'i: ' . $i;
            });
        }

        $this->assertEquals('i: 1', $item->get());
    }

    public function test_expiry_namespace()
    {
        $cache = new FilesystemLayer('expiry', 60, $this->cacheRoot);

        for ($i = 1; $i <= 2; $i++) {
            $item = $cache->getItem('expiry', static function () use ($i) {
                return 'i: ' . $i;
            });
        }

        $this->assertEquals('i: 1', $item->get());
    }

    public function test_flush()
    {
        $cache = new FilesystemLayer('flush', 0, $this->cacheRoot);

        for ($i = 1; $i <= random_int(5, 20); $i++) {
            $cache->getItem('key' . $i, static function () use ($i) {
                return $i;
            });
        }

        $cache->flush();

        $files = iterator_to_array($this->scan($this->cacheRoot . \DIRECTORY_SEPARATOR . 'flush'));

        $this->assertEquals(count($files), 0);
    }

}
