# cache
![Packagist Version](https://img.shields.io/packagist/v/hyqo/cache?style=flat-square)
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/hyqo/cache?style=flat-square)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/hyqo/cache/run-tests?style=flat-square)

## Install

```sh
composer require hyqo/cache
```

## Usage
```php
use Hyqo\Cache\Layer\FilesystemLayer;
use Hyqo\Cache\CacheItem

$cache = new FilesystemLayer('namespace', /*lifetime*/0, 'cache_dir');
$item = $cache->getItem('key', function () {
    return 'ageless cache';
});

$item = $cache->getItem('key', function (\Hyqo\Cache\CacheItem $cacheItem) {
    $cacheItem->setExpiresAfter(60);

    return 'value will be expired after 60 seconds';
});

//expected cache key creation unix time
$item = $cache->getItemCreatedAfter(12345678, 'key', function (\Hyqo\Cache\CacheItem $cacheItem) {
    $cacheItem->setExpiresAfter(60);

    return 'value will be expired after 60 seconds';
});

$value = $item->get();
```
