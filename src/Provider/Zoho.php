<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer\Provider;

use Sunaoka\EmailNormalizer\MailboxProvider;
use Sunaoka\EmailNormalizer\Rules;

/**
 * Zoho Mail provider rules.
 */
final class Zoho extends MailboxProvider
{
    public const FLAGS = Rules::PLUS_ADDRESSING;
    public const MX_DOMAINS = ['zoho.com'];

    #[\Override]
    public static function flags(): int
    {
        return self::FLAGS;
    }
}
