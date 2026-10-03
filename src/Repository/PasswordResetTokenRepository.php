<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PasswordResetToken> */
final class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    public function findByHashForUpdate(string $tokenHash): ?PasswordResetToken
    {
        $token = $this->findOneBy(['tokenHash' => $tokenHash]);
        if (null !== $token) {
            $this->getEntityManager()->lock($token, LockMode::PESSIMISTIC_WRITE);
        }

        return $token;
    }

    public function invalidateForUser(User $user, \DateTimeImmutable $usedAt): void
    {
        $tokens = $this->createQueryBuilder('token')
            ->andWhere('token.user = :user')
            ->andWhere('token.usedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        if (!is_iterable($tokens)) {
            throw new \LogicException('Unexpected password reset token collection.');
        }

        foreach ($tokens as $token) {
            if (!$token instanceof PasswordResetToken) {
                throw new \LogicException('Unexpected password reset token query result.');
            }

            $token->use($usedAt);
        }
    }
}
