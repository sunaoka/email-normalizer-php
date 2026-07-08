<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer\Provider;

use Sunaoka\EmailNormalizer\MailboxProvider;
use Sunaoka\EmailNormalizer\Rules;

/**
 * Yandex Mail provider rules.
 */
final class Yandex extends MailboxProvider
{
    public const FLAGS = Rules::PLUS_ADDRESSING;
    public const MX_DOMAINS = ['mx.yandex.net', 'yandex.ru'];

    #[\Override]
    public static function flags(): int
    {
        return self::FLAGS;
    }
}
