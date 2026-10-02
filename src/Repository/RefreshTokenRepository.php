<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RefreshToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<RefreshToken> */
final class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findByHashForUpdate(string $tokenHash): ?RefreshToken
    {
        $token = $this->createQueryBuilder('token')
            ->andWhere('token.tokenHash = :tokenHash')
            ->setParameter('tokenHash', $tokenHash)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();

        if (null !== $token && !$token instanceof RefreshToken) {
            throw new \LogicException('Unexpected refresh token query result.');
        }

        return $token;
    }
}
