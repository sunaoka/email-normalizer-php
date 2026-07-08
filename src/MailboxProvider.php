<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer;

/**
 * Base contract for mailbox provider normalization rules.
 *
 * Providers declare MX host suffixes and rule flags. They do not perform
 * normalization themselves; Normalizer applies the flags after provider
 * detection.
 */
abstract class MailboxProvider
{
    public const FLAGS = Rules::NONE;

    /** @var list<string> MX host suffixes used to identify this provider. */
    public const MX_DOMAINS = [];

    /**
     * @var list<string>
     *
     * Domains where period stripping is valid. An empty list means every domain
     * matched to the provider may strip periods.
     */
    public const STRIP_PERIOD_DOMAINS = [];

    /**
     * Return provider rule flags.
     *
     * PHP 8.2 does not support typed class constants, so concrete providers
     * expose the flags through this typed method for static analysis.
     */
    abstract public static function flags(): int;
}
