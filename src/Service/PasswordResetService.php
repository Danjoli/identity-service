<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Exception\InvalidPasswordResetToken;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class PasswordResetService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $users,
        private PasswordResetTokenRepository $tokens,
        private RefreshTokenRepository $refreshTokens,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
        private int $tokenTtl,
        private string $sender,
    ) {
    }

    public function requestForEmail(string $email): void
    {
        $user = $this->users->findOneByEmail($email);
        if (null === $user || !$user->isActive() || !$user->isEmailVerified()) {
            return;
        }

        $this->issueAndSend($user);
    }

    public function issueAndSend(User $user): string
    {
        $rawToken = self::generateRawToken();
        $now = new \DateTimeImmutable();
        $this->tokens->invalidateForUser($user, $now);
        $this->entityManager->persist(new PasswordResetToken(
            $user,
            self::hash($rawToken),
            $now->modify(sprintf('+%d seconds', $this->tokenTtl)),
        ));
        $this->entityManager->flush();

        $this->mailer->send((new Email())
            ->from($this->sender)
            ->to($user->getEmail())
            ->subject('Reset your password')
            ->text("Use this token to reset your password:\n\n".$rawToken));

        return $rawToken;
    }

    public function reset(string $rawToken, string $newPassword): void
    {
        $this->entityManager->wrapInTransaction(function () use ($rawToken, $newPassword): void {
            $token = $this->tokens->findByHashForUpdate(self::hash($rawToken));
            $now = new \DateTimeImmutable();
            if (null === $token || !$token->isUsableAt($now) || !$token->getUser()->isActive()) {
                throw new InvalidPasswordResetToken();
            }

            $user = $token->getUser();
            $token->use($now);
            $user->changePasswordHash($this->passwordHasher->hashPassword($user, $newPassword));
            $this->refreshTokens->revokeAllForUser($user, $now);
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
