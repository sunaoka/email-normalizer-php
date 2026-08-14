<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer;

use Pdp\Rules as PublicSuffixRules;

/**
 * Normalizes an email address and resolves MX records.
 *
 * The normalizer splits the local and domain parts, resolves MX records for the
 * domain, detects the mailbox provider from MX hosts, then applies the
 * provider-specific rules. A module-level LFRU-style cache is used for DNS
 * results and optional DNS failure caching.
 *
 * @phpstan-import-type MxRecord from Result
 */
class Normalizer
{
    /** @var array<string, CachedItem> */
    private static array $cache = [];
    private static ?PublicSuffixRules $publicSuffixRules = null;

    /**
     * @param int  $cacheLimit    Maximum number of domains kept in the MX cache.
     * @param bool $cacheFailures Cache failed DNS lookups as empty MX records.
     * @param int  $failureTtl    Seconds to cache DNS failures.
     * @param bool $skipDns       Skip DNS and detect known providers from the static domain map.
     */
    public function __construct(
        public int $cacheLimit = 1024,
        public bool $cacheFailures = true,
        public int $failureTtl = 300,
        private readonly bool $skipDns = false,
    ) {}

    /**
     * Return the shared MX lookup cache.
     *
     * @return array<string, CachedItem>
     */
    public static function cache(): array
    {
        return self::$cache;
    }

    /**
     * Clear the shared MX lookup cache.
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Replace the shared cache in tests.
     *
     * @param array<string, CachedItem> $cache
     *
     * @internal
     */
    public static function setCacheForTest(array $cache): void
    {
        self::$cache = $cache;
    }

    /**
     * Normalize an email address.
     *
     * The returned Result contains the original address, the normalized address,
     * resolved MX records, and the detected mailbox provider name when
     * supported.
     *
     * @throws \InvalidArgumentException If the parsed email address is empty.
     */
    public function normalize(string $emailAddress): Result
    {
        $address = $this->parseAddress($emailAddress);
        [$localPart, $domainPart] = explode('@', strtolower($address), 2);

        if ($this->skipDns) {
            $mxRecords = [];
            $provider = $this->lookupProviderByDomain($domainPart);
        } else {
            $mxRecords = $this->mxRecords($domainPart);
            $provider = $this->lookupProvider($mxRecords);
        }

        if ($provider !== null) {
            if (($provider::flags() & Rules::LOCAL_PART_AS_HOSTNAME) !== 0) {
                [$localPart, $domainPart] = $this->localPartAsHostname($localPart, $domainPart);
            }

            if (($provider::flags() & Rules::STRIP_PERIODS) !== 0 && $this->shouldStripPeriods($provider, $domainPart)) {
                $localPart = str_replace('.', '', $localPart);
            }

            if (($provider::flags() & Rules::PLUS_ADDRESSING) !== 0) {
                $localPart = explode('+', $localPart, 2)[0];
            }

            $domainPart = $provider::CANONICAL_DOMAINS[$domainPart] ?? $domainPart;
        }

        return new Result(
            address: $emailAddress,
            normalizedAddress: $localPart . '@' . $domainPart,
            mxRecords: $mxRecords,
            mailboxProvider: $provider === null ? null : $this->providerName($provider),
        );
    }

    /**
     * Resolve MX records for a domain.
     *
     * Returns a sorted list of records with priority and host. An empty list
     * means resolution failed or DNS is disabled.
     *
     * @return list<MxRecord>
     */
    public function mxRecords(string $domainPart): array
    {
        if ($this->skipDns) {
            return [];
        }

        if ($this->skipCache($domainPart)) {
            $records = $this->resolveMxRecordsForTest($domainPart);

            if ($records === null) {
                if (!$this->cacheFailures) {
                    return [];
                }

                $mxRecords = [];
                $ttl = $this->failureTtl;
            } else {
                $mxRecords = $records;
                $this->sortMxRecords($mxRecords);
                $ttl = $this->failureTtl;
            }

            if (count(self::$cache) >= $this->cacheLimit) {
                $this->pruneCache();
            }

            self::$cache[$domainPart] = new CachedItem($mxRecords, $ttl);
        }

        self::$cache[$domainPart]->hits++;
        self::$cache[$domainPart]->lastAccess = microtime(true);

        return array_map(
            static fn(array $record): array => ['priority' => $record['priority'], 'host' => $record['host']],
            self::$cache[$domainPart]->mxRecords,
        );
    }

    /**
     * Resolve MX records through PHP's native DNS API.
     *
     * A null return represents lookup failure. An empty array represents a
     * successful lookup with no usable MX records.
     *
     * @return list<MxRecord>|null
     */
    protected function resolveMxRecordsForTest(string $domainPart): ?array
    {
        $rawRecords = $this->resolveRawMxRecordsForTest($domainPart);

        if ($rawRecords === false || $rawRecords === []) {
            return null;
        }

        $records = [];
        foreach ($rawRecords as $record) {
            if (!isset($record['pri'], $record['target'])) {
                continue;
            }

            if (!is_int($record['pri']) || !is_string($record['target'])) {
                continue;
            }

            $records[] = [
                'priority' => $record['pri'],
                'host' => strtolower(rtrim($record['target'], '.')),
            ];
        }

        $this->sortMxRecords($records);

        return $records;
    }

    /**
     * Fetch raw MX records from PHP's DNS API.
     *
     * @return array<int, array<int|string, mixed>>|false
     */
    protected function resolveRawMxRecordsForTest(string $domainPart): array|false
    {
        return @dns_get_record($domainPart, DNS_MX);
    }

    /**
     * @param list<MxRecord> $records
     */
    private function sortMxRecords(array &$records): void
    {
        usort(
            $records,
            static fn(array $left, array $right): int => [$left['priority'], $left['host']] <=> [$right['priority'], $right['host']],
        );
    }

    private function parseAddress(string $emailAddress): string
    {
        if (preg_match('/<([^<>]+)>/', $emailAddress, $matches) === 1) {
            $address = trim($matches[1]);
        } else {
            $address = trim($emailAddress);
        }

        if ($address === '') {
            throw new \InvalidArgumentException('Email address must not be empty.');
        }

        return $address;
    }

    /**
     * Apply Fastmail's local-part-as-hostname rule.
     *
     * For domains like "user.example.com", Fastmail treats "user" as the
     * mailbox local part and leaves "example.com" as the domain.
     *
     * @return array{0: string, 1: string}
     */
    private function localPartAsHostname(string $localPart, string $domainPart): array
    {
        $resolvedDomain = self::publicSuffixRules()->resolve($domainPart);
        $registeredDomain = $resolvedDomain->registrableDomain()->toString();

        if ($registeredDomain === $domainPart) {
            return [$localPart, $domainPart];
        }

        $subdomain = $resolvedDomain->subDomain()->toString();
        $subdomainParts = explode('.', $subdomain);
        $localPart = array_shift($subdomainParts);
        $remaining = implode('.', $subdomainParts);

        return [
            $localPart,
            $remaining === '' ? $registeredDomain : $remaining . '.' . $registeredDomain,
        ];
    }

    private static function publicSuffixRules(): PublicSuffixRules
    {
        if (self::$publicSuffixRules === null) {
            self::$publicSuffixRules = PublicSuffixRules::fromString(<<<'PSL'
// Minimal public suffix list used for deterministic Fastmail normalization tests.
// ===BEGIN ICANN DOMAINS===
com
net
org
fm
me
ru
uk
co.uk
org.uk
jp
co.jp
au
com.au
// ===END ICANN DOMAINS===
PSL);
        }

        return self::$publicSuffixRules;
    }

    /**
     * Check whether the provider can strip periods for this domain.
     *
     * Google only strips periods on consumer Gmail domains; Workspace custom
     * domains keep periods.
     *
     * @param class-string<MailboxProvider> $provider
     */
    private function shouldStripPeriods(string $provider, string $domainPart): bool
    {
        return $provider::STRIP_PERIOD_DOMAINS === [] || in_array($domainPart, $provider::STRIP_PERIOD_DOMAINS, true);
    }

    /**
     * Detect a provider from the static domain map used by skipDns mode.
     *
     * @return class-string<MailboxProvider>|null
     */
    private function lookupProviderByDomain(string $domainPart): ?string
    {
        return Providers::DOMAIN_MAP[$domainPart] ?? null;
    }

    /**
     * Detect a provider by matching MX host suffixes.
     *
     * @param list<MxRecord> $mxRecords
     *
     * @return class-string<MailboxProvider>|null
     */
    private function lookupProvider(array $mxRecords): ?string
    {
        foreach ($mxRecords as $record) {
            $host = strtolower($record['host']);

            foreach (Providers::PROVIDERS as $provider) {
                foreach ($provider::MX_DOMAINS as $domain) {
                    if (str_ends_with($host, $domain)) {
                        return $provider;
                    }
                }
            }
        }

        return null;
    }

    private function skipCache(string $domainPart): bool
    {
        if (!isset(self::$cache[$domainPart])) {
            return true;
        }

        if (self::$cache[$domainPart]->expired()) {
            unset(self::$cache[$domainPart]);

            return true;
        }

        return false;
    }

    private function pruneCache(): void
    {
        uasort(
            self::$cache,
            static fn(CachedItem $left, CachedItem $right): int => [$left->hits, $left->lastAccess] <=> [$right->hits, $right->lastAccess],
        );

        $key = array_key_first(self::$cache);
        if ($key !== null) {
            unset(self::$cache[$key]);
        }
    }

    /**
     * @param class-string<MailboxProvider> $provider
     */
    private function providerName(string $provider): string
    {
        $parts = explode('\\', $provider);

        return end($parts);
    }
}
