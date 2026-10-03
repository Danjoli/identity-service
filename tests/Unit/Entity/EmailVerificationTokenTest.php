<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class EmailVerificationTokenTest extends TestCase
{
    public function testTokenIsSingleUseAndExpiresAtBoundary(): void
    {
        $now = new \DateTimeImmutable('2026-10-03T00:00:00+00:00');
        $user = new User('verify@example.com', 'Verify User', 'hash');
        $token = new EmailVerificationToken($user, hash('sha256', 'raw-token'), $now->modify('+1 hour'));

        self::assertSame($user, $token->getUser());
        self::assertTrue($token->isUsableAt($now->modify('+59 minutes')));
        self::assertFalse($token->isUsableAt($now->modify('+1 hour')));

        $token->use($now->modify('+5 minutes'));
        self::assertFalse($token->isUsableAt($now->modify('+6 minutes')));
    }
}
