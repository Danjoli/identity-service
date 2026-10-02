<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Exception\AccountDisabled;
use App\Exception\InvalidRefreshToken;
use App\Repository\RefreshTokenRepository;
use App\ValueObject\RefreshTokenRotation;
use Doctrine\ORM\EntityManagerInterface;

final readonly class RefreshTokenService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RefreshTokenRepository $tokens,
        private int $tokenTtl,
    ) {
    }

    public function issue(User $user): string
    {
        $rawToken = self::generateRawToken();
        $this->entityManager->persist(new RefreshToken(
            $user,
            self::hash($rawToken),
            new \DateTimeImmutable(sprintf('+%d seconds', $this->tokenTtl)),
        ));

        return $rawToken;
    }

    public function rotate(string $rawToken): RefreshTokenRotation
    {
        return $this->entityManager->wrapInTransaction(function () use ($rawToken): RefreshTokenRotation {
            $token = $this->tokens->findByHashForUpdate(self::hash($rawToken));
            $now = new \DateTimeImmutable();

            if (null === $token || !$token->isUsableAt($now)) {
                throw new InvalidRefreshToken();
            }

            if (!$token->getUser()->isActive()) {
                throw new AccountDisabled();
            }

            $token->revoke($now);
            $replacement = $this->issue($token->getUser());

            return new RefreshTokenRotation($token->getUser(), $replacement);
        });
    }

    public function revoke(string $rawToken, User $user): void
    {
        $this->entityManager->wrapInTransaction(function () use ($rawToken, $user): void {
            $token = $this->tokens->findByHashForUpdate(self::hash($rawToken));

            if (null !== $token && $token->getUser()->getId()->equals($user->getId())) {
                $token->revoke(new \DateTimeImmutable());
            }
        });
    }

    public static function hash(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    private static function generateRawToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    }
}
