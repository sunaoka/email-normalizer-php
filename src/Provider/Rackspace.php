<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer\Provider;

use Sunaoka\EmailNormalizer\MailboxProvider;
use Sunaoka\EmailNormalizer\Rules;

/**
 * Rackspace Email provider rules.
 */
final class Rackspace extends MailboxProvider
{
    public const FLAGS = Rules::PLUS_ADDRESSING;
    public const MX_DOMAINS = ['emailsrvr.com'];

    #[\Override]
    public static function flags(): int
    {
        return self::FLAGS;
    }
}
