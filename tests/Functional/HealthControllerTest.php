<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

final class HealthControllerTest extends WebTestCase
{
    public function testLivenessDoesNotDependOnExternalServices(): void
    {
        $client = self::createClient();
        $requestId = Uuid::v7()->toRfc4122();
        $client->request('GET', '/health/live', server: ['HTTP_X_REQUEST_ID' => $requestId]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('x-request-id', $requestId);
        self::assertJsonStringEqualsJsonString('{"status":"alive"}', (string) $client->getResponse()->getContent());
    }

    public function testReadinessChecksDatabaseConnection(): void
    {
        $client = self::createClient();
        $client->request('GET', '/health/ready');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"status":"ready","checks":{"database":"up"}}',
            (string) $client->getResponse()->getContent(),
        );
    }
}
