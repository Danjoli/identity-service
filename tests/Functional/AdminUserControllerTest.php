<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AdminUserControllerTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin-authorization-test@example.com';
    private const USER_EMAIL = 'user-authorization-test@example.com';

    protected function tearDown(): void
    {
        if (null !== self::$kernel) {
            $entityManager = self::getContainer()->get(EntityManagerInterface::class);
            self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
            $entityManager->createQuery('DELETE FROM App\Entity\User user WHERE user.email IN (:emails)')
                ->setParameter('emails', [self::ADMIN_EMAIL, self::USER_EMAIL])
                ->execute();
        }

        parent::tearDown();
    }

    public function testAnonymousAndRegularUsersCannotListUsers(): void
    {
        $client = self::createClient();
        $client->jsonRequest('GET', '/api/v1/admin/users');
        self::assertResponseStatusCodeSame(401);

        $user = $this->createUser(self::USER_EMAIL, false);
        $client->jsonRequest('GET', '/api/v1/admin/users', server: $this->authorization($user));
        self::assertResponseStatusCodeSame(403);
    }

    public function testAdministratorCanListUsers(): void
    {
        $client = self::createClient();
        $admin = $this->createUser(self::ADMIN_EMAIL, true);
        $this->createUser(self::USER_EMAIL, false);

        $client->jsonRequest('GET', '/api/v1/admin/users', server: $this->authorization($admin));

        self::assertResponseIsSuccessful();
        $payload = $this->responseData($client);
        self::assertIsArray($payload['data']);
        $emails = array_column($payload['data'], 'email');
        self::assertContains(self::ADMIN_EMAIL, $emails);
        self::assertContains(self::USER_EMAIL, $emails);
    }

    public function testAdministratorCanPromoteAndDisableAnotherUser(): void
    {
        $client = self::createClient();
        $admin = $this->createUser(self::ADMIN_EMAIL, true);
        $user = $this->createUser(self::USER_EMAIL, false);

        $client->jsonRequest('PATCH', '/api/v1/admin/users/'.$user->getId()->toRfc4122(), [
            'roles' => ['ROLE_ADMIN'],
            'status' => 'disabled',
        ], server: $this->authorization($admin));

        self::assertResponseIsSuccessful();
        $payload = $this->responseData($client);
        self::assertIsArray($payload['data']);
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $payload['data']['roles']);
        self::assertSame('disabled', $payload['data']['status']);
    }

    public function testRegularUserCannotChangeAuthorizationData(): void
    {
        $client = self::createClient();
        $admin = $this->createUser(self::ADMIN_EMAIL, true);
        $user = $this->createUser(self::USER_EMAIL, false);

        $client->jsonRequest('PATCH', '/api/v1/admin/users/'.$user->getId()->toRfc4122(), [
            'roles' => ['ROLE_ADMIN'],
            'status' => 'active',
        ], server: $this->authorization($user));

        self::assertResponseStatusCodeSame(403);
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $admin->getRoles());
    }

    public function testAdministratorCannotChangeOwnAuthorization(): void
    {
        $client = self::createClient();
        $admin = $this->createUser(self::ADMIN_EMAIL, true);

        $client->jsonRequest('PATCH', '/api/v1/admin/users/'.$admin->getId()->toRfc4122(), [
            'roles' => ['ROLE_USER'],
            'status' => 'disabled',
        ], server: $this->authorization($admin));

        self::assertResponseStatusCodeSame(409);
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $admin->getRoles());
        self::assertTrue($admin->isActive());
    }

    public function testRejectsUnknownRoles(): void
    {
        $client = self::createClient();
        $admin = $this->createUser(self::ADMIN_EMAIL, true);
        $user = $this->createUser(self::USER_EMAIL, false);

        $client->jsonRequest('PATCH', '/api/v1/admin/users/'.$user->getId()->toRfc4122(), [
            'roles' => ['ROLE_SUPER_ADMIN'],
            'status' => 'active',
        ], server: $this->authorization($admin));

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    private function createUser(string $email, bool $admin): User
    {
        $user = new User($email, 'Authorization Test', 'unused-hash');
        if ($admin) {
            $user->replaceRoles(['ROLE_ADMIN']);
        }

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    /** @return array{HTTP_AUTHORIZATION: string} */
    private function authorization(User $user): array
    {
        $tokenManager = self::getContainer()->get(JWTTokenManagerInterface::class);
        self::assertInstanceOf(JWTTokenManagerInterface::class, $tokenManager);

        return ['HTTP_AUTHORIZATION' => 'Bearer '.$tokenManager->create($user)];
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
