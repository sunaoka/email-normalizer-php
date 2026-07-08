<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer;

/**
 * Contains data produced by the email normalization process.
 *
 * @phpstan-type MxRecord array{priority: int, host: string}
 */
readonly class Result
{
    /**
     * @param string         $address           Original address passed to the normalizer.
     * @param string         $normalizedAddress Provider-normalized email address.
     * @param list<MxRecord> $mxRecords
     * @param string|null    $mailboxProvider   Detected provider name, or null when unsupported or unknown.
     */
    public function __construct(
        public string $address,
        public string $normalizedAddress,
        public array $mxRecords,
        public ?string $mailboxProvider = null,
    ) {}
}
