<?php

namespace Hyqo\Cache;

class CacheItem
{
    public const VERSION = '2';

    /** @var CacheLayerInterface */
    private $pool;

    private $key;

    /** @var ?string */
    private $value;

    private $isHit;

    private $isLazy = false;

    private $createdAt;

    private $expiresAt = 0;

    private $expiresAfter = 0;

    private $meta = [];

    public function __construct(
        CacheLayerInterface $pool,
        ?string $key,
        ?string $value,
        bool $isHit,
        ?int $createdAt = null
    ) {
        $this->pool = $pool;
        $this->key = $key;
        $this->value = $value;
        $this->isHit = $isHit;
        $this->createdAt = $createdAt ?? time();
    }

    public function __toString()
    {
        return $this->value ?? '';
    }

    public function pool(): CacheLayerInterface
    {
        return $this->pool;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function get(): ?string
    {
        return $this->value;
    }

    public function set(?string $value): self
    {
        $this->createdAt = time();
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

    public function setExpiresAt(int $timestamp): self
    {
        $this->expiresAt = $timestamp;
        $this->expiresAfter = $timestamp ? $timestamp - time() : 0;

        return $this;
    }

    public function setExpiresAfter(int $seconds): self
    {
        $this->expiresAt = $seconds ? time() + $seconds : 0;
        $this->expiresAfter = $seconds;

        return $this;
    }

    public function pack(): string
    {
        return Meta::pack(self::VERSION, $this->createdAt, $this->expiresAt) . PHP_EOL . $this;
    }
}
