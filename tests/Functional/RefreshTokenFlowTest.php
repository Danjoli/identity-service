<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RefreshTokenFlowTest extends WebTestCase
{
    private const EMAIL = 'refresh-test@example.com';
    private const PASSWORD = 'StrongPassword1';

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

    public function testRotatesOnceAndRejectsReplay(): void
    {
        $client = self::createClient();
        $this->createUser();
        $tokens = $this->login($client);

        $client->jsonRequest('POST', '/api/v1/auth/refresh', [
            'refreshToken' => $tokens['refreshToken'],
        ]);
        self::assertResponseIsSuccessful();
        $rotated = $this->tokenData($client);
        self::assertNotSame($tokens['refreshToken'], $rotated['refreshToken']);

        $client->jsonRequest('POST', '/api/v1/auth/refresh', [
            'refreshToken' => $tokens['refreshToken'],
        ]);
        self::assertResponseStatusCodeSame(401);
    }

    public function testLogoutRevokesRefreshToken(): void
    {
        $client = self::createClient();
        $this->createUser();
        $tokens = $this->login($client);

        $client->jsonRequest('POST', '/api/v1/auth/logout', [
            'refreshToken' => $tokens['refreshToken'],
        ], server: ['HTTP_AUTHORIZATION' => 'Bearer '.$tokens['accessToken']]);
        self::assertResponseStatusCodeSame(204);

        $client->jsonRequest('POST', '/api/v1/auth/refresh', [
            'refreshToken' => $tokens['refreshToken'],
        ]);
        self::assertResponseStatusCodeSame(401);
    }

    private function createUser(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $hasher);
        $user = new User(self::EMAIL, 'Refresh Test', 'pending-hash');
        $user->changePasswordHash($hasher->hashPassword($user, self::PASSWORD));
        $entityManager->persist($user);
        $entityManager->flush();
    }

    /** @return array{accessToken: string, refreshToken: string} */
    private function login(KernelBrowser $client): array
    {
        $client->jsonRequest('POST', '/api/v1/auth/login', [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ]);
        self::assertResponseIsSuccessful();

        return $this->tokenData($client);
    }

    /** @return array{accessToken: string, refreshToken: string} */
    private function tokenData(KernelBrowser $client): array
    {
        $response = $client->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        $content = $response->getContent();
        self::assertIsString($content);
        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertIsArray($payload['data']);
        self::assertIsString($payload['data']['accessToken']);
        self::assertIsString($payload['data']['refreshToken']);

        return [
            'accessToken' => $payload['data']['accessToken'],
            'refreshToken' => $payload['data']['refreshToken'],
        ];
    }
}
