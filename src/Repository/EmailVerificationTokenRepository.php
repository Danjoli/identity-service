<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EmailVerificationToken> */
final class EmailVerificationTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailVerificationToken::class);
    }

    public function findByHashForUpdate(string $tokenHash): ?EmailVerificationToken
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
            throw new \LogicException('Unexpected e-mail verification token collection.');
        }

        foreach ($tokens as $token) {
            if (!$token instanceof EmailVerificationToken) {
                throw new \LogicException('Unexpected e-mail verification token query result.');
            }

            $token->use($usedAt);
        }
    }
}
