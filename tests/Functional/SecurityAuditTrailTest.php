<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\AuditEvent;
use App\Entity\User;
use App\Repository\AuditEventRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityAuditTrailTest extends WebTestCase
{
    private const EMAIL = 'audit-admin@example.com';

    protected function tearDown(): void
    {
        if (null !== self::$kernel) {
            $entityManager = self::getContainer()->get(EntityManagerInterface::class);
            self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
            $entityManager->createQuery('DELETE FROM App\Entity\AuditEvent event')->execute();
            $entityManager->createQuery('DELETE FROM App\Entity\User user WHERE user.email = :email')
                ->setParameter('email', self::EMAIL)->execute();
        }
        parent::tearDown();
    }

    public function testAuditLoggerCapturesIdentityAndRedactsSecrets(): void
    {
        self::createClient();
        $user = $this->createAdmin();
        $logger = self::getContainer()->get(AuditLogger::class);
        self::assertInstanceOf(AuditLogger::class, $logger);
        $logger->record('security.test', $user, $user, [
            'password' => 'plain-text',
            'nested' => ['accessToken' => 'secret-token', 'safe' => 'visible'],
        ]);

        $events = self::getContainer()->get(AuditEventRepository::class);
        self::assertInstanceOf(AuditEventRepository::class, $events);
        $event = $events->findOneBy(['eventType' => 'security.test']);
        self::assertInstanceOf(AuditEvent::class, $event);
        self::assertSame(self::EMAIL, $event->getActorEmail());
        self::assertSame(self::EMAIL, $event->getSubjectEmail());
        self::assertSame('[REDACTED]', $event->getContext()['password']);
        $nested = $event->getContext()['nested'];
        self::assertIsArray($nested);
        self::assertSame('[REDACTED]', $nested['accessToken']);
        self::assertSame('visible', $nested['safe']);
        self::assertStringNotContainsString('plain-text', json_encode($event->getContext(), JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('secret-token', json_encode($event->getContext(), JSON_THROW_ON_ERROR));
    }

    public function testAdministratorCanQueryAuditEvents(): void
    {
        $client = self::createClient();
        $admin = $this->createAdmin();
        $logger = self::getContainer()->get(AuditLogger::class);
        self::assertInstanceOf(AuditLogger::class, $logger);
        $logger->record('authentication.logout', $admin, $admin);
        $tokens = self::getContainer()->get(JWTTokenManagerInterface::class);
        self::assertInstanceOf(JWTTokenManagerInterface::class, $tokens);

        $client->jsonRequest('GET', '/api/v1/admin/audit-events', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$tokens->create($admin),
        ]);

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertIsArray($payload['data']);
        self::assertIsArray($payload['data'][0]);
        self::assertSame('authentication.logout', $payload['data'][0]['eventType']);
    }

    private function createAdmin(): User
    {
        $user = new User(self::EMAIL, 'Audit Admin', 'unused-hash');
        $user->replaceRoles(['ROLE_ADMIN']);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
