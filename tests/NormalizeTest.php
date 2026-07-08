<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

use function Sunaoka\EmailNormalizer\normalize;

use Sunaoka\EmailNormalizer\Normalizer;
use Sunaoka\EmailNormalizer\Result;

final class NormalizeTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        Normalizer::clearCache();
    }

    public function testInvalidDomainPart(): void
    {
        $address = 'user@invalid.invalid';

        $result = normalize($address);

        self::assertInstanceOf(Result::class, $result);
        self::assertSame($address, $result->address);
        self::assertSame($address, $result->normalizedAddress);
        self::assertSame([], $result->mxRecords);
        self::assertNull($result->mailboxProvider);
    }

    public function testEmptyAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email address must not be empty.');

        normalize('');
    }

    public function testBlankAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email address must not be empty.');

        normalize('   ');
    }

    public function testApple(): void
    {
        $this->assertNormalized('user+test@example.com', 'user@example.com', [['priority' => 10, 'host' => 'mx01.mail.icloud.com']], 'Apple');
    }

    public function testFastmailPlusAddressing(): void
    {
        $this->assertNormalized('user+test@example.com', 'user@example.com', [['priority' => 10, 'host' => 'in1-smtp.messagingengine.com']], 'Fastmail');
    }

    public function testFastmailLocalPartAsHostname(): void
    {
        $this->assertNormalized('testing@user.example.com', 'user@example.com', [['priority' => 10, 'host' => 'in1-smtp.messagingengine.com']], 'Fastmail');
    }

    public function testFastmailMultiSegmentTldNoSubdomain(): void
    {
        $this->assertNormalized('user@example.co.uk', 'user@example.co.uk', [['priority' => 10, 'host' => 'in1-smtp.messagingengine.com']], 'Fastmail');
    }

    public function testFastmailMultiSegmentTldWithSubdomain(): void
    {
        $this->assertNormalized('testing@user.example.com.au', 'user@example.com.au', [['priority' => 10, 'host' => 'in1-smtp.messagingengine.com']], 'Fastmail');
    }

    public function testFastmailComplexMultiSegmentTld(): void
    {
        $this->assertNormalized('testing@user.mail.example.org.uk', 'user@mail.example.org.uk', [['priority' => 10, 'host' => 'in1-smtp.messagingengine.com']], 'Fastmail');
    }

    public function testFastmailDeepSubdomainSingleTld(): void
    {
        $this->assertNormalized('testing@user.mail.eu.example.com', 'user@mail.eu.example.com', [['priority' => 10, 'host' => 'in1-smtp.messagingengine.com']], 'Fastmail');
    }

    public function testGoogleConsumerGmail(): void
    {
        $this->assertNormalized('u.s.e.r+tag@gmail.com', 'user@gmail.com', [['priority' => 1, 'host' => 'aspmx.l.google.com']], 'Google');
    }

    public function testGoogleConsumerGooglemail(): void
    {
        $this->assertNormalized('u.s.e.r+tag@googlemail.com', 'user@googlemail.com', [['priority' => 1, 'host' => 'aspmx.l.google.com']], 'Google');
    }

    public function testGoogleWorkspacePreservesPeriods(): void
    {
        $this->assertNormalized('u.s.e.r+tag@example.com', 'u.s.e.r@example.com', [['priority' => 1, 'host' => 'aspmx.l.google.com']], 'Google');
    }

    public function testMicrosoft(): void
    {
        $this->assertNormalized('user+test@example.com', 'user@example.com', [['priority' => 10, 'host' => 'domain-com.mail.protection.outlook.com']], 'Microsoft');
    }

    public function testProtonMail(): void
    {
        $this->assertNormalized('user+test@example.com', 'user@example.com', [['priority' => 5, 'host' => 'mail.protonmail.ch']], 'ProtonMail');
    }

    public function testRackspace(): void
    {
        $this->assertNormalized('user+test@example.com', 'user@example.com', [['priority' => 10, 'host' => 'mx1.emailsrvr.com']], 'Rackspace');
    }

    public function testYahoo(): void
    {
        $this->assertNormalized('user-keyword@example.com', 'user-keyword@example.com', [['priority' => 1, 'host' => 'mta5.am0.yahoodns.net']], 'Yahoo');
    }

    public function testYandex(): void
    {
        $this->assertNormalized('user+test@example.com', 'user@example.com', [['priority' => 10, 'host' => 'mx.yandex.net']], 'Yandex');
    }

    public function testZoho(): void
    {
        $this->assertNormalized('user+test@example.com', 'user@example.com', [['priority' => 10, 'host' => 'mx.zoho.com']], 'Zoho');
    }

    public function testGmail(): void
    {
        $this->assertSkipDnsProvider('gmail.com', 'Google');
    }

    public function testGooglemail(): void
    {
        $this->assertSkipDnsProvider('googlemail.com', 'Google');
    }

    public function testOutlook(): void
    {
        $this->assertSkipDnsProvider('outlook.com', 'Microsoft');
    }

    public function testHotmail(): void
    {
        $this->assertSkipDnsProvider('hotmail.com', 'Microsoft');
    }

    public function testLive(): void
    {
        $this->assertSkipDnsProvider('live.com', 'Microsoft');
    }

    public function testMsn(): void
    {
        $this->assertSkipDnsProvider('msn.com', 'Microsoft');
    }

    public function testIcloud(): void
    {
        $this->assertSkipDnsProvider('icloud.com', 'Apple');
    }

    public function testMe(): void
    {
        $this->assertSkipDnsProvider('me.com', 'Apple');
    }

    public function testMac(): void
    {
        $this->assertSkipDnsProvider('mac.com', 'Apple');
    }

    public function testFastmail(): void
    {
        $this->assertSkipDnsProvider('fastmail.com', 'Fastmail');
    }

    public function testFastmailFm(): void
    {
        $this->assertSkipDnsProvider('fastmail.fm', 'Fastmail');
    }

    public function testProtonmailSkipDns(): void
    {
        $this->assertSkipDnsProvider('protonmail.com', 'ProtonMail');
    }

    public function testProtonMe(): void
    {
        $this->assertSkipDnsProvider('proton.me', 'ProtonMail');
    }

    public function testPmMe(): void
    {
        $this->assertSkipDnsProvider('pm.me', 'ProtonMail');
    }

    public function testYahooSkipDns(): void
    {
        $this->assertSkipDnsProvider('yahoo.com', 'Yahoo');
    }

    public function testYahooCoUk(): void
    {
        $this->assertSkipDnsProvider('yahoo.co.uk', 'Yahoo');
    }

    public function testYahooCoJp(): void
    {
        $this->assertSkipDnsProvider('yahoo.co.jp', 'Yahoo');
    }

    public function testYmail(): void
    {
        $this->assertSkipDnsProvider('ymail.com', 'Yahoo');
    }

    public function testAol(): void
    {
        $this->assertSkipDnsProvider('aol.com', 'Yahoo');
    }

    public function testYandexCom(): void
    {
        $this->assertSkipDnsProvider('yandex.com', 'Yandex');
    }

    public function testYandexRu(): void
    {
        $this->assertSkipDnsProvider('yandex.ru', 'Yandex');
    }

    public function testYaRu(): void
    {
        $this->assertSkipDnsProvider('ya.ru', 'Yandex');
    }

    public function testZohoSkipDns(): void
    {
        $this->assertSkipDnsProvider('zoho.com', 'Zoho');
    }

    public function testZohomail(): void
    {
        $this->assertSkipDnsProvider('zohomail.com', 'Zoho');
    }

    public function testSkipDnsRulesApplied(): void
    {
        $result = normalize('u.s.e.r+tag@gmail.com', skipDns: true);

        self::assertSame('user@gmail.com', $result->normalizedAddress);
        self::assertSame('Google', $result->mailboxProvider);
    }

    public function testMicrosoftPlusAddressing(): void
    {
        $result = normalize('user+tag@outlook.com', skipDns: true);

        self::assertSame('user@outlook.com', $result->normalizedAddress);
    }

    public function testSkipDnsUnknownDomain(): void
    {
        $result = normalize('user@example.invalid', skipDns: true);

        self::assertNull($result->mailboxProvider);
        self::assertSame([], $result->mxRecords);
    }

    public function testDnsNotCalled(): void
    {
        $normalizer = new class (skipDns: true) extends Normalizer {
            #[\Override]
            public function mxRecords(string $domainPart): array
            {
                throw new \LogicException('DNS should not be called');
            }
        };

        $result = $normalizer->normalize('test@gmail.com');

        self::assertSame('Google', $result->mailboxProvider);
    }

    public function testAsyncNormalizer(): void
    {
        $normalizer = new Normalizer(skipDns: true);

        $result = $normalizer->normalize('u.s.e.r+tag@gmail.com');

        self::assertSame('user@gmail.com', $result->normalizedAddress);
        self::assertSame('Google', $result->mailboxProvider);
        self::assertSame([], $result->mxRecords);
    }

    /**
     * @param list<array{priority: int, host: string}> $mxRecords
     */
    private function assertNormalized(string $address, string $normalized, array $mxRecords, string $provider): void
    {
        $normalizer = new class ($mxRecords) extends Normalizer {
            /**
             * @param list<array{priority: int, host: string}> $mxRecords
             */
            public function __construct(private readonly array $mxRecords)
            {
                parent::__construct();
            }

            #[\Override]
            public function mxRecords(string $domainPart): array
            {
                return $this->mxRecords;
            }
        };

        $result = $normalizer->normalize($address);

        self::assertInstanceOf(Result::class, $result);
        self::assertSame($address, $result->address);
        self::assertSame($normalized, $result->normalizedAddress);
        self::assertSame($mxRecords, $result->mxRecords);
        self::assertSame($provider, $result->mailboxProvider);
    }

    private function assertSkipDnsProvider(string $domain, string $provider): void
    {
        $result = normalize('user@' . $domain, skipDns: true);

        self::assertSame($provider, $result->mailboxProvider);
        self::assertSame([], $result->mxRecords);
    }
}
