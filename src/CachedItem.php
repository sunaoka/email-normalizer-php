<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer;

/**
 * Cached MX lookup result used by the shared LFRU cache.
 *
 * @phpstan-import-type MxRecord from Result
 */
final class CachedItem
{
    public float $cachedAt;
    public int $hits = 0;
    public float $lastAccess = 0.0;

    /**
     * @param int            $ttl Seconds before this item expires.
     * @param list<MxRecord> $mxRecords
     */
    public function __construct(
        public array $mxRecords,
        public int $ttl,
    ) {
        $this->cachedAt = microtime(true);
    }

    /**
     * Determine whether the cached DNS result exceeded its TTL.
     */
    public function expired(): bool
    {
        return (microtime(true) - $this->cachedAt) > $this->ttl;
    }
}
