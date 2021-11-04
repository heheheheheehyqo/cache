<?php

namespace Hyqo\Cache\Layer;

interface Lazieable
{
    public function persist(): bool;
}
