<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer\Provider;

use Sunaoka\EmailNormalizer\MailboxProvider;
use Sunaoka\EmailNormalizer\Rules;

/**
 * Google provider rules.
 *
 * Consumer Gmail domains strip periods; Google Workspace custom domains do not.
 */
final class Google extends MailboxProvider
{
    public const FLAGS = Rules::PLUS_ADDRESSING | Rules::STRIP_PERIODS;
    public const MX_DOMAINS = ['google.com', 'googlemail.com'];
    public const STRIP_PERIOD_DOMAINS = ['gmail.com', 'googlemail.com'];

    #[\Override]
    public static function flags(): int
    {
        return self::FLAGS;
    }
}
