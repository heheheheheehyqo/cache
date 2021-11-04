<?php

namespace Hyqo\Cache;

class CacheItem
{
    /** @var Cache */
    private $cache;

    private $key;

    private $value;

    private $isHit;

    private $isLazy = false;

    private $expiresAt = 0;

    private $expiresAfter = 0;

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

    public function get()
    {
        return $this->value;
    }

    public function set($value): self
    {
        $this->value = $value;

        $this->cache->save([$this]);

        return $this;
    }

    public function save(): bool
    {
        return $this->cache->save([$this]);
    }

    public function delete(): void
    {
        $this->cache->deleteItem($this->getKey());
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

    public function lazy(): self
    {
        $this->isLazy = true;

        return $this;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    public function isLazy(): bool
    {
        return $this->isLazy;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    public function getExpiresAfter(): int
    {
        return $this->expiresAfter;
    }

    public function hasExpiry(): bool
    {
        return $this->expiresAfter || $this->expiresAt;
    }

    public function resetExpiry(): self
    {
        $this->expiresAt = 0;
        $this->expiresAfter = 0;

        return $this;
    }

    public function expiresAt(int $timestamp): self
    {
        $this->expiresAt = $timestamp;
        $this->expiresAfter = $timestamp - time();

        return $this;
    }

    public function expiresAfter(int $seconds): self
    {
        $this->expiresAt = time() + $seconds;
        $this->expiresAfter = $seconds;

        return $this;
    }
}
