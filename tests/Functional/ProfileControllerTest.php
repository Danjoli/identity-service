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

final class ProfileControllerTest extends WebTestCase
{
    private const EMAIL = 'profile-test@example.com';
    private const OTHER_EMAIL = 'other-profile-test@example.com';
    private const PASSWORD = 'StrongPassword1';
    private const NEW_PASSWORD = 'NewStrongPassword2';

    protected function tearDown(): void
    {
        if (null !== self::$kernel) {
            $entityManager = self::getContainer()->get(EntityManagerInterface::class);
            self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
            $entityManager->createQuery('DELETE FROM App\Entity\User user WHERE user.email IN (:emails)')
                ->setParameter('emails', [self::EMAIL, self::OTHER_EMAIL])
                ->execute();
        }

        parent::tearDown();
    }

    public function testRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->jsonRequest('GET', '/api/v1/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testShowsOnlyTheAuthenticatedProfile(): void
    {
        $client = self::createClient();
        $this->createUser(self::EMAIL, 'Profile Owner');
        $this->createUser(self::OTHER_EMAIL, 'Other User');
        $tokens = $this->login($client, self::EMAIL, self::PASSWORD);

        $client->jsonRequest('GET', '/api/v1/me', server: $this->authorization($tokens));

        self::assertResponseIsSuccessful();
        $data = $this->responseData($client);
        self::assertSame(self::EMAIL, $data['email']);
        self::assertSame('Profile Owner', $data['displayName']);
        self::assertArrayNotHasKey('password', $data);
    }

    public function testUpdatesOnlyTheAuthenticatedUsersSafeProfileData(): void
    {
        $client = self::createClient();
        $this->createUser(self::EMAIL, 'Before Update');
        $this->createUser(self::OTHER_EMAIL, 'Other User');
        $tokens = $this->login($client, self::EMAIL, self::PASSWORD);

        $client->jsonRequest('PATCH', '/api/v1/me', [
            'displayName' => '  Updated Name  ',
        ], server: $this->authorization($tokens));

        self::assertResponseIsSuccessful();
        $data = $this->responseData($client);
        self::assertSame('Updated Name', $data['displayName']);
        self::assertSame(['ROLE_USER'], $data['roles']);
        $users = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $users);
        self::assertSame('Updated Name', $users->findOneByEmail(self::EMAIL)?->getDisplayName());
        self::assertSame('Other User', $users->findOneByEmail(self::OTHER_EMAIL)?->getDisplayName());
    }

    public function testPasswordChangeRequiresCurrentPassword(): void
    {
        $client = self::createClient();
        $this->createUser(self::EMAIL, 'Profile Owner');
        $tokens = $this->login($client, self::EMAIL, self::PASSWORD);

        $client->jsonRequest('PUT', '/api/v1/me/password', [
            'currentPassword' => 'WrongPassword1',
            'newPassword' => self::NEW_PASSWORD,
        ], server: $this->authorization($tokens));

        self::assertResponseStatusCodeSame(401);
        $client->jsonRequest('POST', '/api/v1/auth/login', [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ]);
        self::assertResponseIsSuccessful();
    }

    public function testPasswordChangeRevokesRefreshSessionsAndChangesCredentials(): void
    {
        $client = self::createClient();
        $this->createUser(self::EMAIL, 'Profile Owner');
        $tokens = $this->login($client, self::EMAIL, self::PASSWORD);

        $client->jsonRequest('PUT', '/api/v1/me/password', [
            'currentPassword' => self::PASSWORD,
            'newPassword' => self::NEW_PASSWORD,
        ], server: $this->authorization($tokens));
        self::assertResponseStatusCodeSame(204);

        $client->jsonRequest('POST', '/api/v1/auth/refresh', [
            'refreshToken' => $tokens['refreshToken'],
        ]);
        self::assertResponseStatusCodeSame(401);

        $client->jsonRequest('POST', '/api/v1/auth/login', [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(401);

        $newTokens = $this->login($client, self::EMAIL, self::NEW_PASSWORD);
        self::assertNotEmpty($newTokens['accessToken']);
    }

    private function createUser(string $email, string $displayName): User
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $hasher);
        $user = new User($email, $displayName, 'pending-hash');
        $user->changePasswordHash($hasher->hashPassword($user, self::PASSWORD));
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    /** @return array{accessToken: string, refreshToken: string} */
    private function login(KernelBrowser $client, string $email, string $password): array
    {
        $client->jsonRequest('POST', '/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);
        self::assertResponseIsSuccessful();
        $data = $this->responseData($client);
        self::assertIsString($data['accessToken']);
        self::assertIsString($data['refreshToken']);

        return ['accessToken' => $data['accessToken'], 'refreshToken' => $data['refreshToken']];
    }

    /** @param array{accessToken: string, refreshToken: string} $tokens
     * @return array{HTTP_AUTHORIZATION: string}
     */
    private function authorization(array $tokens): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer '.$tokens['accessToken']];
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
        self::assertIsArray($payload['data']);

        return $payload['data'];
    }
}
