<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Uid\Uuid;

final class ApiProblemDetailsTest extends WebTestCase
{
    public function testValidationFailuresAreGroupedByField(): void
    {
        $client = self::createClient();
        $client->jsonRequest('POST', '/api/v1/auth/register', [
            'email' => 'invalid-email',
            'displayName' => '',
            'password' => 'weak',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
        $problem = $this->problem($client);
        self::assertSame('validation_failed', $problem['code']);
        self::assertSame(422, $problem['status']);
        self::assertIsArray($problem['violations']);
        self::assertArrayHasKey('email', $problem['violations']);
        self::assertArrayHasKey('displayName', $problem['violations']);
        self::assertArrayHasKey('password', $problem['violations']);
    }

    public function testMalformedJsonHasAStableProblemCode(): void
    {
        $client = self::createClient();
        $client->request(
            'POST',
            '/api/v1/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"email":',
        );

        self::assertResponseStatusCodeSame(400);
        self::assertSame('malformed_json', $this->problem($client)['code']);
    }

    public function testUnsupportedMediaTypeHasAStableProblemCode(): void
    {
        $client = self::createClient();
        $client->request(
            'POST',
            '/api/v1/auth/login',
            server: ['CONTENT_TYPE' => 'text/plain'],
            content: 'email=test@example.com',
        );

        self::assertResponseStatusCodeSame(415);
        self::assertSame('unsupported_media_type', $this->problem($client)['code']);
    }

    public function testRequestIdentifierIsReturnedInHeaderAndProblemBody(): void
    {
        $client = self::createClient();
        $requestId = Uuid::v7()->toRfc4122();
        $client->jsonRequest(
            'POST',
            '/api/v1/auth/login',
            ['email' => 'unknown@example.com', 'password' => 'StrongPassword1'],
            server: ['HTTP_X_REQUEST_ID' => $requestId],
        );

        self::assertResponseStatusCodeSame(401);
        self::assertResponseHeaderSame('x-request-id', $requestId);
        self::assertSame($requestId, $this->problem($client)['requestId']);
    }

    /** @return array<mixed> */
    private function problem(KernelBrowser $client): array
    {
        $response = $client->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        $content = $response->getContent();
        self::assertIsString($content);
        $problem = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($problem);
        self::assertArrayHasKey('type', $problem);
        self::assertArrayHasKey('title', $problem);
        self::assertArrayHasKey('detail', $problem);
        self::assertArrayHasKey('instance', $problem);
        self::assertArrayHasKey('requestId', $problem);

        return $problem;
    }
}
