<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer\Provider;

use Sunaoka\EmailNormalizer\MailboxProvider;
use Sunaoka\EmailNormalizer\Rules;

/**
 * Fastmail provider rules, including subdomain-as-local-part aliases.
 */
final class Fastmail extends MailboxProvider
{
    public const FLAGS = Rules::PLUS_ADDRESSING | Rules::LOCAL_PART_AS_HOSTNAME;
    public const MX_DOMAINS = ['messagingengine.com'];

    #[\Override]
    public static function flags(): int
    {
        return self::FLAGS;
    }
}
