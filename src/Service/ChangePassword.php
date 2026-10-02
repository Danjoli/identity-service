<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ChangePasswordRequest;
use App\Entity\User;
use App\Exception\InvalidCurrentPassword;
use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class ChangePassword
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private RefreshTokenRepository $refreshTokens,
    ) {
    }

    public function change(User $user, ChangePasswordRequest $request): void
    {
        if (!$this->passwordHasher->isPasswordValid($user, $request->currentPassword)) {
            throw new InvalidCurrentPassword();
        }

        $this->entityManager->wrapInTransaction(function () use ($user, $request): void {
            $user->changePasswordHash($this->passwordHasher->hashPassword($user, $request->newPassword));
            $this->refreshTokens->revokeAllForUser($user, new \DateTimeImmutable());
        });
    }
}
