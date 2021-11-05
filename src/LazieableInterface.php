<?php

namespace Hyqo\Cache;

interface LazieableInterface
{
    public function persist(): bool;
}
