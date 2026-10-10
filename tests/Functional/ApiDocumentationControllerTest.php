<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ApiDocumentationControllerTest extends WebTestCase
{
    public function testSwaggerUiIsAvailable(): void
    {
        $client = self::createClient();
        $client->request('GET', '/docs');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'text/html; charset=UTF-8');
        self::assertStringContainsString('SwaggerUIBundle', (string) $client->getResponse()->getContent());
        self::assertStringContainsString('/docs/openapi.yaml', (string) $client->getResponse()->getContent());
    }

    public function testVersionedOpenApiContractIsPublished(): void
    {
        $client = self::createClient();
        $client->request('GET', '/docs/openapi.yaml');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/yaml; charset=UTF-8');
        $response = $client->getResponse();
        self::assertInstanceOf(BinaryFileResponse::class, $response);
        $contract = file_get_contents($response->getFile()->getPathname());
        self::assertIsString($contract);
        self::assertStringContainsString('title: Identity Service API', $contract);
        self::assertStringContainsString('bearerAuth:', $contract);
    }

    public function testSwaggerAssetsAreServedLocally(): void
    {
        $client = self::createClient();
        $client->request('GET', '/docs/assets/swagger-ui.css');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('cache-control', 'immutable, max-age=31536000, public');
        self::assertResponseHeaderSame('content-type', 'text/css; charset=UTF-8');
    }

    public function testUnknownSwaggerAssetIsNotExposed(): void
    {
        $client = self::createClient();
        $client->request('GET', '/docs/assets/package.json');

        self::assertResponseStatusCodeSame(404);
    }
}
