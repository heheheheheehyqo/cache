<?php

namespace Hyqo\Cache;

class CacheItem
{
    /** @var Cache */
    private $cache;

    private $key;

    private $value;

    private $isHit;

    private $expiry = 0;

    private $meta = [];

    public function __construct(Cache $cache, string $key, $value, bool $isHit)
    {
        $this->cache = $cache;
        $this->key = $key;
        $this->value = $value;
        $this->isHit = $isHit;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getExpiry(): int
    {
        return $this->expiry;
    }

    public function get()
    {
        return $this->value;
    }

    public function set($value): self
    {
        $this->value = $value;

        $this->cache->save($this);

        return $this;
    }

    public function delete(): void
    {
        $this->cache->delete($this->getKey());
    }

    public function getMeta(string $key)
    {
        return $this->meta[$key] ?? null;
    }

    public function setMeta(string $key, $value): self
    {
        $this->meta[$key] = $value;

        return $this;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    public function isExpired(): bool
    {
        return ($this->expiry && $this->expiry < time());
    }

    public function expiresAt(int $timestamp): self
    {
        $this->expiry = $timestamp;

        return $this;
    }

    public function expiresAfter(int $seconds): self
    {
        $this->expiresAt(time() + $seconds);

        return $this;
    }
}
