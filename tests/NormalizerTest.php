<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Sunaoka\EmailNormalizer\CachedItem;
use Sunaoka\EmailNormalizer\Normalizer;

final class NormalizerTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        Normalizer::clearCache();
    }

    public function testMxRecords(): void
    {
        $rawRecords = dns_get_record('gmail.com', DNS_MX);
        self::assertIsArray($rawRecords);

        $expectation = [];
        foreach ($rawRecords as $record) {
            if (!isset($record['pri'], $record['target'])) {
                continue;
            }

            if (!is_int($record['pri']) || !is_string($record['target'])) {
                continue;
            }

            $expectation[] = [
                'priority' => $record['pri'],
                'host' => strtolower(rtrim($record['target'], '.')),
            ];
        }

        usort(
            $expectation,
            static fn(array $left, array $right): int => [$left['priority'], $left['host']] <=> [$right['priority'], $right['host']],
        );

        self::assertSame($expectation, (new Normalizer())->mxRecords('gmail.com'));
    }

    public function testCache(): void
    {
        $normalizer = new class extends Normalizer {
            public int $calls = 0;

            #[\Override]
            protected function resolveMxRecordsForTest(string $domainPart): array
            {
                $this->calls++;

                return [
                    ['priority' => 10, 'host' => 'z.example.com'],
                    ['priority' => 1, 'host' => 'a.example.com'],
                ];
            }
        };

        $expected = [
            ['priority' => 1, 'host' => 'a.example.com'],
            ['priority' => 10, 'host' => 'z.example.com'],
        ];

        self::assertSame($expected, $normalizer->mxRecords('example.com'));
        self::assertSame($expected, $normalizer->mxRecords('example.com'));
        self::assertSame(1, $normalizer->calls);
        self::assertSame(2, Normalizer::cache()['example.com']->hits);

        Normalizer::clearCache();

        self::assertArrayNotHasKey('example.com', Normalizer::cache());
        self::assertArrayNotHasKey('foo', Normalizer::cache());
    }

    public function testFailureCached(): void
    {
        $normalizer = new class extends Normalizer {
            #[\Override]
            protected function resolveMxRecordsForTest(string $domainPart): ?array
            {
                return null;
            }
        };

        self::assertSame([], $normalizer->mxRecords('example.invalid'));
        self::assertArrayHasKey('example.invalid', Normalizer::cache());
    }

    public function testFailureNotCached(): void
    {
        $normalizer = new class (cacheFailures: false) extends Normalizer {
            #[\Override]
            protected function resolveMxRecordsForTest(string $domainPart): ?array
            {
                return null;
            }
        };

        self::assertSame([], $normalizer->mxRecords('example.invalid'));
        self::assertSame([], Normalizer::cache());
    }

    public function testCacheExpiration(): void
    {
        $normalizer = new class extends Normalizer {
            public int $calls = 0;

            #[\Override]
            protected function resolveMxRecordsForTest(string $domainPart): array
            {
                $this->calls++;

                return [['priority' => 1, 'host' => 'mx.example.com']];
            }
        };

        $normalizer->mxRecords('example.com');
        Normalizer::cache()['example.com']->ttl = 0;
        usleep(1000);
        $normalizer->mxRecords('example.com');

        self::assertSame(2, $normalizer->calls);
    }

    public function testCacheMaxSize(): void
    {
        $normalizer = new class (cacheLimit: 2) extends Normalizer {
            #[\Override]
            protected function resolveMxRecordsForTest(string $domainPart): array
            {
                return [['priority' => 1, 'host' => 'mx.' . $domainPart]];
            }
        };

        $old = new CachedItem([], 60);
        $old->hits = 1;
        $old->lastAccess = 1.0;
        $new = new CachedItem([], 60);
        $new->hits = 3;
        $new->lastAccess = 3.0;

        Normalizer::setCacheForTest(['old.example' => $old, 'new.example' => $new]);  // @phpstan-ignore staticMethod.internal

        $normalizer->mxRecords('added.example');

        self::assertArrayNotHasKey('old.example', Normalizer::cache());
        self::assertArrayHasKey('new.example', Normalizer::cache());
        self::assertArrayHasKey('added.example', Normalizer::cache());
    }

    public function testEmptyMxList(): void
    {
        $normalizer = new class extends Normalizer {
            #[\Override]
            public function mxRecords(string $domainPart): array
            {
                return [];
            }
        };

        $result = $normalizer->normalize('foo@bar.com');

        self::assertSame('foo@bar.com', $result->normalizedAddress);
        self::assertNull($result->mailboxProvider);
        self::assertSame([], $result->mxRecords);
    }

    public function testWeirdMxList(): void
    {
        $normalizer = new class extends Normalizer {
            #[\Override]
            public function mxRecords(string $domainPart): array
            {
                return [
                    ['priority' => 1, 'host' => 'unknown.example.com'],
                    ['priority' => 10, 'host' => 'aspmx.l.google.com'],
                ];
            }
        };

        $result = $normalizer->normalize('f.o.o+bar@gmail.com');

        self::assertSame('foo@gmail.com', $result->normalizedAddress);
        self::assertSame('Google', $result->mailboxProvider);
    }
}
