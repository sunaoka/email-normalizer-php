<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer;

use Sunaoka\EmailNormalizer\Provider\Apple;
use Sunaoka\EmailNormalizer\Provider\Fastmail;
use Sunaoka\EmailNormalizer\Provider\Google;
use Sunaoka\EmailNormalizer\Provider\Microsoft;
use Sunaoka\EmailNormalizer\Provider\ProtonMail;
use Sunaoka\EmailNormalizer\Provider\Rackspace;
use Sunaoka\EmailNormalizer\Provider\Yahoo;
use Sunaoka\EmailNormalizer\Provider\Yandex;
use Sunaoka\EmailNormalizer\Provider\Zoho;

/**
 * Registry of supported providers and static domain lookup data.
 *
 * DOMAIN_MAP is used only when DNS is skipped. Normal DNS mode detects
 * providers from MX host suffixes.
 */
final class Providers
{
    /** @var list<class-string<MailboxProvider>> */
    public const PROVIDERS = [
        Apple::class,
        Fastmail::class,
        Google::class,
        Microsoft::class,
        ProtonMail::class,
        Rackspace::class,
        Yahoo::class,
        Yandex::class,
        Zoho::class,
    ];

    /** @var array<string, class-string<MailboxProvider>> */
    public const DOMAIN_MAP = [
        'icloud.com' => Apple::class,
        'me.com' => Apple::class,
        'mac.com' => Apple::class,
        'fastmail.com' => Fastmail::class,
        'fastmail.fm' => Fastmail::class,
        'gmail.com' => Google::class,
        'googlemail.com' => Google::class,
        'outlook.com' => Microsoft::class,
        'hotmail.com' => Microsoft::class,
        'live.com' => Microsoft::class,
        'msn.com' => Microsoft::class,
        'proton.me' => ProtonMail::class,
        'protonmail.com' => ProtonMail::class,
        'pm.me' => ProtonMail::class,
        'yahoo.com' => Yahoo::class,
        'yahoo.co.uk' => Yahoo::class,
        'yahoo.co.jp' => Yahoo::class,
        'ymail.com' => Yahoo::class,
        'aol.com' => Yahoo::class,
        'yandex.com' => Yandex::class,
        'yandex.ru' => Yandex::class,
        'ya.ru' => Yandex::class,
        'zoho.com' => Zoho::class,
        'zohomail.com' => Zoho::class,
    ];
}
