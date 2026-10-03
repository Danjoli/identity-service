<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use App\Exception\InvalidEmailVerificationToken;
use App\Repository\EmailVerificationTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class EmailVerificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $users,
        private EmailVerificationTokenRepository $tokens,
        private MailerInterface $mailer,
        private int $tokenTtl,
        private string $sender,
    ) {
    }

    public function requestForEmail(string $email): void
    {
        $user = $this->users->findOneByEmail($email);
        if (null === $user || $user->isEmailVerified()) {
            return;
        }

        $this->issueAndSend($user);
    }

    public function issueAndSend(User $user): string
    {
        $rawToken = self::generateRawToken();
        $now = new \DateTimeImmutable();
        $this->tokens->invalidateForUser($user, $now);
        $this->entityManager->persist(new EmailVerificationToken(
            $user,
            self::hash($rawToken),
            $now->modify(sprintf('+%d seconds', $this->tokenTtl)),
        ));
        $this->entityManager->flush();

        $this->mailer->send((new Email())
            ->from($this->sender)
            ->to($user->getEmail())
            ->subject('Verify your e-mail address')
            ->text("Use this token to verify your e-mail address:\n\n".$rawToken));

        return $rawToken;
    }

    public function confirm(string $rawToken): User
    {
        return $this->entityManager->wrapInTransaction(function () use ($rawToken): User {
            $token = $this->tokens->findByHashForUpdate(self::hash($rawToken));
            $now = new \DateTimeImmutable();
            if (null === $token || !$token->isUsableAt($now)) {
                throw new InvalidEmailVerificationToken();
            }

            $token->use($now);
            $token->getUser()->verifyEmail($now);

            return $token->getUser();
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
