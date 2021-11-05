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

$cache = new FilesystemLayer('namespace', /*lifetime*/0, 'cache_dir');
$item = $cache->getItem('key', function () {
    return 'computed value if there is nothing in the cache';
});
$value = $item->get();
```
