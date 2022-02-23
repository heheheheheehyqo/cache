<?php

namespace Hyqo\Cache;

class Meta
{
    public static function pack(string $version, int $createdAt, int $expiresAt): string
    {
        return implode(',', [$version, $createdAt, $expiresAt]);
    }

    public static function unpack(string $string): array
    {
        $parts = explode(',', $string, 3);

        if (3 !== count($parts)) {
            return ['', 0, 0];
        }

        [$version, $createdAt, $expiresAt] = $parts;

        if ($version !== CacheItem::VERSION) {
            return ['', 0, 0];
        }

        return [$version, (int)$createdAt, (int)$expiresAt];
    }

    public static function isHit(string $version, int $createdAt, int $expiresAt, ?int $minCreationTime): bool
    {
        if ($version !== CacheItem::VERSION) {
            return false;
        }

        if (null !== $minCreationTime && $createdAt < $minCreationTime) {
            return false;
        }

        return !$expiresAt || $expiresAt >= time();
    }
}
