<?php

namespace Hyqo\Cache;

class CacheItem
{
    /** @var CacheInterface */
    private $pool;

    private $key;

    private $value;

    private $isHit;

    private $isLazy = false;

    private $expiresAt = 0;

    private $expiresAfter = 0;

    private $meta = [];

    public function __construct(CacheInterface $pool, string $key, $value, bool $isHit)
    {
        $this->pool = $pool;
        $this->key = $key;
        $this->value = $value;
        $this->isHit = $isHit;
    }

    public function pool(): CacheInterface
    {
        return $this->pool;
    }

    public function key(): string
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

        $this->pool->save([$this]);

        return $this;
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
