<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class PasswordResetTokenTest extends TestCase
{
    public function testTokenIsUsableOnlyBeforeExpirationAndConsumption(): void
    {
        $now = new \DateTimeImmutable('2026-10-03T00:00:00+00:00');
        $user = new User('reset@example.com', 'Reset User', 'hash');
        $token = new PasswordResetToken($user, hash('sha256', 'raw-token'), $now->modify('+30 minutes'));

        self::assertTrue($token->isUsableAt($now));
        self::assertFalse($token->isUsableAt($now->modify('+30 minutes')));

        $token->use($now->modify('+1 minute'));
        self::assertFalse($token->isUsableAt($now->modify('+2 minutes')));
    }
}
