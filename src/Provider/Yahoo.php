<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer\Provider;

use Sunaoka\EmailNormalizer\MailboxProvider;
use Sunaoka\EmailNormalizer\Rules;

/**
 * Yahoo provider detection.
 *
 * Yahoo disposable addresses use nickname-keyword aliases, where the nickname
 * alone is not necessarily deliverable, so no normalization rules are applied.
 */
final class Yahoo extends MailboxProvider
{
    public const FLAGS = Rules::NONE;
    public const MX_DOMAINS = ['yahoodns.net'];

    #[\Override]
    public static function flags(): int
    {
        return self::FLAGS;
    }
}
