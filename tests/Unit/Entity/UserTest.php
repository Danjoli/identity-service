<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Enum\AccountStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

final class UserTest extends TestCase
{
    public function testCreatesAnActiveUserWithNormalizedIdentity(): void
    {
        $user = new User('  ADA@Example.COM ', '  Ada Lovelace  ', 'password-hash');

        self::assertInstanceOf(UuidV7::class, $user->getId());
        self::assertSame('ada@example.com', $user->getEmail());
        self::assertSame('ada@example.com', $user->getUserIdentifier());
        self::assertSame('Ada Lovelace', $user->getDisplayName());
        self::assertSame('password-hash', $user->getPassword());
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertSame(AccountStatus::Active, $user->getStatus());
        self::assertTrue($user->isActive());
        self::assertSame($user->getCreatedAt(), $user->getUpdatedAt());
    }

    public function testMaintainsMandatoryUserRoleWithoutDuplicates(): void
    {
        $user = new User('ada@example.com', 'Ada', 'password-hash');

        $user->replaceRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ADMIN']);

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testCanDisableAndReactivateAccount(): void
    {
        $user = new User('ada@example.com', 'Ada', 'password-hash');

        $user->disable();
        self::assertFalse($user->isActive());
        self::assertSame(AccountStatus::Disabled, $user->getStatus());

        $user->activate();
        self::assertTrue($user->isActive());
        self::assertSame(AccountStatus::Active, $user->getStatus());
    }

    public function testRejectsEmptyEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new User('  ', 'Ada', 'password-hash');
    }
}
