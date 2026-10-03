<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Service\PasswordResetService;
use App\Service\TokenPairIssuer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordResetFlowTest extends WebTestCase
{
    private const EMAIL = 'password-reset-test@example.com';
    private const PASSWORD = 'StrongPassword1';
    private const NEW_PASSWORD = 'NewStrongPassword2';

    protected function tearDown(): void
    {
        if (null !== self::$kernel) {
            $entityManager = self::getContainer()->get(EntityManagerInterface::class);
            self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
            $entityManager->createQuery('DELETE FROM App\Entity\User user WHERE user.email = :email')
                ->setParameter('email', self::EMAIL)
                ->execute();
        }

        parent::tearDown();
    }

    public function testResetIsSingleUseChangesPasswordAndRevokesSessions(): void
    {
        $client = self::createClient();
        $user = $this->createVerifiedUser();
        $tokens = $this->tokenPairIssuer()->issue($user);
        $rawToken = $this->passwordReset()->issueAndSend($user);

        $client->jsonRequest('POST', '/api/v1/auth/password/reset', [
            'token' => $rawToken,
            'newPassword' => self::NEW_PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(204);

        $client->jsonRequest('POST', '/api/v1/auth/password/reset', [
            'token' => $rawToken,
            'newPassword' => self::NEW_PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(400);

        $client->jsonRequest('POST', '/api/v1/auth/refresh', [
            'refreshToken' => $tokens['refreshToken'],
        ]);
        self::assertResponseStatusCodeSame(401);

        $client->jsonRequest('POST', '/api/v1/auth/login', [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(401);

        $client->jsonRequest('POST', '/api/v1/auth/login', [
            'email' => self::EMAIL,
            'password' => self::NEW_PASSWORD,
        ]);
        self::assertResponseIsSuccessful();
    }

    public function testExpiredTokenCannotResetPassword(): void
    {
        $client = self::createClient();
        $user = $this->createVerifiedUser();
        $rawToken = str_repeat('b', 64);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->persist(new PasswordResetToken(
            $user,
            PasswordResetService::hash($rawToken),
            new \DateTimeImmutable('-1 second'),
        ));
        $entityManager->flush();

        $client->jsonRequest('POST', '/api/v1/auth/password/reset', [
            'token' => $rawToken,
            'newPassword' => self::NEW_PASSWORD,
        ]);

        self::assertResponseStatusCodeSame(400);
    }

    public function testNewRequestInvalidatesPreviousToken(): void
    {
        $client = self::createClient();
        $user = $this->createVerifiedUser();
        $passwordReset = $this->passwordReset();
        $firstToken = $passwordReset->issueAndSend($user);
        $secondToken = $passwordReset->issueAndSend($user);

        $client->jsonRequest('POST', '/api/v1/auth/password/reset', [
            'token' => $firstToken,
            'newPassword' => self::NEW_PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(400);

        $client->jsonRequest('POST', '/api/v1/auth/password/reset', [
            'token' => $secondToken,
            'newPassword' => self::NEW_PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(204);
    }

    public function testRequestDoesNotRevealAccountExistenceOrEligibility(): void
    {
        $client = self::createClient();
        $this->createVerifiedUser();

        $client->jsonRequest('POST', '/api/v1/auth/password/request', ['email' => self::EMAIL]);
        self::assertResponseStatusCodeSame(202);
        $existingResponse = $client->getResponse()->getContent();

        $client->jsonRequest('POST', '/api/v1/auth/password/request', ['email' => 'unknown@example.com']);
        self::assertResponseStatusCodeSame(202);
        self::assertSame($existingResponse, $client->getResponse()->getContent());
    }

    private function createVerifiedUser(): User
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $hasher);
        $user = new User(self::EMAIL, 'Password Reset Test', 'pending-hash');
        $user->changePasswordHash($hasher->hashPassword($user, self::PASSWORD));
        $user->verifyEmail(new \DateTimeImmutable());
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function passwordReset(): PasswordResetService
    {
        $service = self::getContainer()->get(PasswordResetService::class);
        self::assertInstanceOf(PasswordResetService::class, $service);

        return $service;
    }

    private function tokenPairIssuer(): TokenPairIssuer
    {
        $service = self::getContainer()->get(TokenPairIssuer::class);
        self::assertInstanceOf(TokenPairIssuer::class, $service);

        return $service;
    }
}
