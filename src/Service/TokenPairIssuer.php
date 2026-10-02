<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final readonly class TokenPairIssuer
{
    public function __construct(
        private JWTTokenManagerInterface $accessTokens,
        private RefreshTokenService $refreshTokens,
        private EntityManagerInterface $entityManager,
        private int $accessTokenTtl,
    ) {
    }

    /** @return array{tokenType: 'Bearer', accessToken: string, expiresIn: int, refreshToken: string} */
    public function issue(User $user): array
    {
        $refreshToken = $this->refreshTokens->issue($user);
        $this->entityManager->flush();

        return $this->tokensFor($user, $refreshToken);
    }

    /** @return array{tokenType: 'Bearer', accessToken: string, expiresIn: int, refreshToken: string} */
    public function rotate(string $refreshToken): array
    {
        $rotation = $this->refreshTokens->rotate($refreshToken);

        return $this->tokensFor($rotation->user, $rotation->refreshToken);
    }

    /** @return array{tokenType: 'Bearer', accessToken: string, expiresIn: int, refreshToken: string} */
    private function tokensFor(User $user, string $refreshToken): array
    {
        return [
            'tokenType' => 'Bearer',
            'accessToken' => $this->accessTokens->create($user),
            'expiresIn' => $this->accessTokenTtl,
            'refreshToken' => $refreshToken,
        ];
    }
}
