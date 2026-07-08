# email-normalizer-php

`email-normalizer-php` is a PHP port of [`gmr/email-normalize`](https://github.com/gmr/email-normalize).

It normalizes email addresses by applying mailbox provider-specific rules such as plus addressing, Gmail dot handling, and Fastmail subdomain aliases. It detects providers from MX records through PHP's native DNS functions, or from a static domain map when DNS lookups are skipped.

This package is for canonicalizing email addresses. It is not an email validator and does not check whether a mailbox exists.

## Requirements

- PHP 8.2 or later
- Composer

## Installation

```bash
composer require sunaoka/email-normalizer-php
```

## Basic Usage

```php
<?php

use function Sunaoka\EmailNormalizer\normalize;

$result = normalize('u.s.e.r+tag@gmail.com');

echo $result->address;           // u.s.e.r+tag@gmail.com
echo $result->normalizedAddress; // user@gmail.com
echo $result->mailboxProvider;   // Google
```

`normalize()` creates a temporary `Normalizer` instance. Reuse `Normalizer` directly when normalizing many addresses or when cache options matter.

## Skipping DNS Lookups

Use `skipDns: true` when DNS resolution is not desired. In this mode, the package does not call DNS, detects well-known mailbox providers from a static domain map, and returns an empty MX record list.

```php
<?php

use function Sunaoka\EmailNormalizer\normalize;

$result = normalize('user+tag@outlook.com', skipDns: true);

echo $result->normalizedAddress; // user@outlook.com
echo $result->mailboxProvider;   // Microsoft
```

## Reusing a Normalizer

Create a `Normalizer` instance when normalizing multiple addresses. The MX cache is shared at the package level and respects the configured cache limit and failure caching options.

```php
<?php

use Sunaoka\EmailNormalizer\Normalizer;

$normalizer = new Normalizer(
    cacheLimit: 1024,
    cacheFailures: true,
    failureTtl: 300,
);

$result = $normalizer->normalize('name+tag@example.com');
```

Constructor options:

- `cacheLimit`: maximum number of domains kept in the shared MX cache. Default: `1024`.
- `cacheFailures`: cache failed DNS lookups as empty MX records. Default: `true`.
- `failureTtl`: seconds to cache failed DNS lookups. Default: `300`.
- `skipDns`: skip DNS and use the static domain map. Default: `false`.

## Result Object

`normalize()` and `Normalizer::normalize()` return `Sunaoka\EmailNormalizer\Result`.

```php
readonly class Result
{
    public string $address;
    public string $normalizedAddress;
    /** @var list<array{priority: int, host: string}> */
    public array $mxRecords;
    public ?string $mailboxProvider;
}
```

Fields:

- `address`: original input string.
- `normalizedAddress`: normalized email address.
- `mxRecords`: sorted MX records as `priority` and `host` pairs.
- `mailboxProvider`: detected provider name, or `null` when unknown.

An empty `mxRecords` list means DNS was skipped or MX lookup failed.

## Supported Providers

- Apple
- Fastmail
- Google
- Microsoft
- ProtonMail
- Rackspace
- Yahoo
- Yandex
- Zoho

## Normalization Rules

- Plus addressing providers strip the `+tag` part from the local part.
- Gmail and Googlemail strip periods from the local part.
- Google Workspace custom domains keep periods but strip plus addressing.
- Fastmail can treat the left-most subdomain as the local part.
- Yahoo is detected, but no local-part normalization is applied.

Examples:

```php
normalize('u.s.e.r+tag@gmail.com', skipDns: true)->normalizedAddress;
// user@gmail.com

normalize('user+tag@outlook.com', skipDns: true)->normalizedAddress;
// user@outlook.com

normalize('testing@user.example.com')->normalizedAddress;
// user@example.com when the domain is detected as Fastmail
```

## DNS and Caching

MX records are resolved with `dns_get_record($domain, DNS_MX)`. Records are normalized to lowercase host names and sorted by priority, then host.

The cache is shared by all `Normalizer` instances. When the cache reaches `cacheLimit`, the entry with the lowest hit count and oldest access time is removed.

Failed MX lookups return an empty list. When `cacheFailures` is enabled, failures are cached for `failureTtl` seconds.

## Development

```bash
composer install
composer test
composer analyse
composer format
```

`composer analyse` runs PHPStan at `max` level with bleeding edge rules enabled.

## License

BSD-3-Clause.

This project is based on the behavior of [`gmr/email-normalize`](https://github.com/gmr/email-normalize).
