<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginControllerTest extends WebTestCase
{
    private const EMAIL = 'login-test@example.com';
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

    public function testIssuesAccessTokenForValidCredentials(): void
    {
        $client = self::createClient();
        $this->createUser();

        $client->jsonRequest('POST', '/api/v1/auth/login', [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ]);

        self::assertResponseIsSuccessful();
        $payload = $this->responseData($client);
        self::assertIsArray($payload['data']);
        self::assertSame('Bearer', $payload['data']['tokenType']);
        self::assertSame(900, $payload['data']['expiresIn']);
        self::assertIsString($payload['data']['accessToken']);
        self::assertCount(3, explode('.', $payload['data']['accessToken']));
        self::assertIsString($payload['data']['refreshToken']);
        self::assertGreaterThanOrEqual(64, strlen($payload['data']['refreshToken']));
    }

    public function testUnknownEmailAndWrongPasswordReturnSameError(): void
    {
        $client = self::createClient();
        $this->createUser();

        $client->jsonRequest('POST', '/api/v1/auth/login', [
            'email' => 'unknown@example.com',
            'password' => self::PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(401);
        $unknownResponse = $this->responseData($client);

        $client->jsonRequest('POST', '/api/v1/auth/login', [
            'email' => self::EMAIL,
            'password' => 'IncorrectPassword1',
        ]);
        self::assertResponseStatusCodeSame(401);
        $wrongPasswordResponse = $this->responseData($client);
        self::assertSame($unknownResponse['status'], $wrongPasswordResponse['status']);
        self::assertSame($unknownResponse['code'], $wrongPasswordResponse['code']);
        self::assertSame($unknownResponse['detail'], $wrongPasswordResponse['detail']);
    }

    public function testRejectsDisabledAccount(): void
    {
        $client = self::createClient();
        $user = $this->createUser();
        $user->disable();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->flush();

        $client->jsonRequest('POST', '/api/v1/auth/login', [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ]);

        self::assertResponseStatusCodeSame(403);
        $payload = $this->responseData($client);
        self::assertSame('account_disabled', $payload['code']);
    }

    private function createUser(): User
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $hasher);

        $user = new User(self::EMAIL, 'Login Test', 'pending-hash');
        $user->changePasswordHash($hasher->hashPassword($user, self::PASSWORD));
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    /** @return array<mixed> */
    private function responseData(KernelBrowser $client): array
    {
        $response = $client->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        $content = $response->getContent();
        self::assertIsString($content);
        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
