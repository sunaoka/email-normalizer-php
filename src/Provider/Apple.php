<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer\Provider;

use Sunaoka\EmailNormalizer\MailboxProvider;
use Sunaoka\EmailNormalizer\Rules;

/**
 * Apple iCloud Mail provider rules.
 */
final class Apple extends MailboxProvider
{
    public const FLAGS = Rules::PLUS_ADDRESSING;
    public const MX_DOMAINS = ['icloud.com'];

    #[\Override]
    public static function flags(): int
    {
        return self::FLAGS;
    }
}
