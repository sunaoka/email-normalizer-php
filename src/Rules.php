<?php

declare(strict_types=1);

namespace Sunaoka\EmailNormalizer;

/**
 * Bit flags describing provider-specific address aliasing behavior.
 */
final class Rules
{
    public const NONE = 0;

    /** Strip "+tag" suffixes from the local part. */
    public const PLUS_ADDRESSING = 1;

    /** Treat the left-most subdomain as the local part. Used by Fastmail. */
    public const LOCAL_PART_AS_HOSTNAME = 2;

    /** Strip periods from the local part when the provider allows it. */
    public const STRIP_PERIODS = 4;
}
