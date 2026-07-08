<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer;

/**
 * Normalize an email address with mailbox provider specific rules.
 *
 * This function is the simple synchronous entry point. It creates a temporary
 * Normalizer instance, resolves MX records unless $skipDns is enabled, and
 * returns the original address, normalized address, MX records, and detected
 * mailbox provider.
 *
 * @throws \InvalidArgumentException If the parsed email address is empty.
 */
function normalize(string $emailAddress, bool $skipDns = false): Result
{
    return (new Normalizer(skipDns: $skipDns))->normalize($emailAddress);
}
