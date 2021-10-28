<?php

namespace PHPSTORM_META {

    registerArgumentsSet('meta_key', 'file');

    expectedArguments(\Hyqo\Cache\CacheItem::getMeta(), 0, argumentsSet('meta_key'));
    expectedArguments(\Hyqo\Cache\CacheItem::setMeta(), 0, argumentsSet('meta_key'));
}
