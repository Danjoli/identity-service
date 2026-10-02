<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterUserControllerTest extends WebTestCase
{
    private const EMAIL = 'registration-test@example.com';

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

    public function testRegistersAUserWithAHashedPassword(): void
    {
        $client = self::createClient();
        $client->jsonRequest('POST', '/api/v1/auth/register', [
            'email' => 'REGISTRATION-TEST@Example.com',
            'displayName' => 'Ada Lovelace',
            'password' => 'StrongPassword1',
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertResponseHeaderSame('content-type', 'application/json');
        $payload = $this->responseData($client);
        self::assertIsArray($payload['data']);
        self::assertSame(self::EMAIL, $payload['data']['email']);
        self::assertSame('Ada Lovelace', $payload['data']['displayName']);
        self::assertArrayNotHasKey('password', $payload['data']);

        $users = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $users);
        $user = $users->findOneByEmail(self::EMAIL);
        self::assertInstanceOf(User::class, $user);

        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $hasher);
        self::assertTrue($hasher->isPasswordValid($user, 'StrongPassword1'));
        self::assertNotSame('StrongPassword1', $user->getPassword());
    }

    public function testRejectsDuplicateEmail(): void
    {
        $client = self::createClient();
        $request = [
            'email' => self::EMAIL,
            'displayName' => 'Ada Lovelace',
            'password' => 'StrongPassword1',
        ];

        $client->jsonRequest('POST', '/api/v1/auth/register', $request);
        self::assertResponseStatusCodeSame(201);

        $client->jsonRequest('POST', '/api/v1/auth/register', $request);
        self::assertResponseStatusCodeSame(409);
        $payload = $this->responseData($client);
        self::assertIsArray($payload['error']);
        self::assertSame('email_already_exists', $payload['error']['code']);
    }

    public function testRejectsWeakPassword(): void
    {
        $client = self::createClient();
        $client->jsonRequest('POST', '/api/v1/auth/register', [
            'email' => self::EMAIL,
            'displayName' => 'Ada Lovelace',
            'password' => 'weak',
        ]);

        self::assertResponseStatusCodeSame(422);
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
