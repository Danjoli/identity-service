<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

final class EmailVerificationFlowTest extends WebTestCase
{
    private const EMAIL = 'verification-test@example.com';

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

    public function testVerificationTokenIsSingleUseAndPersistsVerifiedStatus(): void
    {
        $client = self::createClient();
        $user = $this->createUser();
        $token = $this->verification()->issueAndSend($user);

        $client->jsonRequest('POST', '/api/v1/auth/email/verify', ['token' => $token]);
        self::assertResponseIsSuccessful();
        $data = $this->responseData($client);
        self::assertSame(self::EMAIL, $data['email']);
        self::assertIsString($data['emailVerifiedAt']);

        $users = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $users);
        self::assertTrue($users->findOneByEmail(self::EMAIL)?->isEmailVerified());

        $client->jsonRequest('POST', '/api/v1/auth/email/verify', ['token' => $token]);
        self::assertResponseStatusCodeSame(400);
    }

    public function testExpiredTokenIsRejected(): void
    {
        $client = self::createClient();
        $user = $this->createUser();
        $rawToken = str_repeat('a', 64);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->persist(new EmailVerificationToken(
            $user,
            EmailVerificationService::hash($rawToken),
            new \DateTimeImmutable('-1 second'),
        ));
        $entityManager->flush();

        $client->jsonRequest('POST', '/api/v1/auth/email/verify', ['token' => $rawToken]);

        self::assertResponseStatusCodeSame(400);
        self::assertFalse($user->isEmailVerified());
    }

    public function testNewTokenInvalidatesThePreviousToken(): void
    {
        $client = self::createClient();
        $user = $this->createUser();
        $verification = $this->verification();
        $firstToken = $verification->issueAndSend($user);
        $secondToken = $verification->issueAndSend($user);

        $client->jsonRequest('POST', '/api/v1/auth/email/verify', ['token' => $firstToken]);
        self::assertResponseStatusCodeSame(400);

        $client->jsonRequest('POST', '/api/v1/auth/email/verify', ['token' => $secondToken]);
        self::assertResponseIsSuccessful();
    }

    public function testResendDoesNotRevealWhetherAnAccountExists(): void
    {
        $client = self::createClient();
        $this->createUser();

        $client->jsonRequest('POST', '/api/v1/auth/email/resend', ['email' => self::EMAIL]);
        self::assertResponseStatusCodeSame(202);
        $existingResponse = $client->getResponse()->getContent();

        $client->jsonRequest('POST', '/api/v1/auth/email/resend', ['email' => 'unknown@example.com']);
        self::assertResponseStatusCodeSame(202);
        self::assertSame($existingResponse, $client->getResponse()->getContent());
    }

    private function createUser(): User
    {
        $user = new User(self::EMAIL, 'Verification Test', 'unused-hash');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function verification(): EmailVerificationService
    {
        $verification = self::getContainer()->get(EmailVerificationService::class);
        self::assertInstanceOf(EmailVerificationService::class, $verification);

        return $verification;
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
