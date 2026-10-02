<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\RefreshToken;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class RefreshTokenTest extends TestCase
{
    public function testTokenIsUsableBeforeExpirationAndRevocation(): void
    {
        $now = new \DateTimeImmutable();
        $token = new RefreshToken(
            new User('ada@example.com', 'Ada', 'password-hash'),
            hash('sha256', 'raw-token'),
            $now->modify('+1 hour'),
        );

        self::assertTrue($token->isUsableAt($now));
        self::assertNotSame('raw-token', $token->getTokenHash());

        $token->revoke($now);
        self::assertFalse($token->isUsableAt($now));
    }

    public function testExpiredTokenIsNotUsable(): void
    {
        $now = new \DateTimeImmutable();
        $token = new RefreshToken(
            new User('ada@example.com', 'Ada', 'password-hash'),
            hash('sha256', 'raw-token'),
            $now->modify('-1 second'),
        );

        self::assertFalse($token->isUsableAt($now));
    }
}
