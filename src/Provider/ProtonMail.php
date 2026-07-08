<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer\Provider;

use Sunaoka\EmailNormalizer\MailboxProvider;
use Sunaoka\EmailNormalizer\Rules;

/**
 * Proton Mail provider rules.
 */
final class ProtonMail extends MailboxProvider
{
    public const FLAGS = Rules::PLUS_ADDRESSING;
    public const MX_DOMAINS = ['protonmail.ch'];

    #[\Override]
    public static function flags(): int
    {
        return self::FLAGS;
    }
}
