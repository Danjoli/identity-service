<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Uid\Uuid;

final class ApiSecurityHardeningTest extends WebTestCase
{
    public function testLoginAttemptsAreRateLimited(): void
    {
        $client = self::createClient();
        $server = ['REMOTE_ADDR' => '198.51.100.42', 'HTTP_X_TEST_RATE_LIMIT' => '1'];
        $email = 'rate-limit-'.Uuid::v7()->toRfc4122().'@example.com';

        for ($attempt = 1; $attempt <= 10; ++$attempt) {
            $client->jsonRequest('POST', '/api/v1/auth/login', [
                'email' => $email,
                'password' => 'StrongPassword1',
            ], server: $server);
            self::assertResponseStatusCodeSame(401);
        }

        $client->jsonRequest('POST', '/api/v1/auth/login', [
            'email' => $email,
            'password' => 'StrongPassword1',
        ], server: $server);

        self::assertResponseStatusCodeSame(429);
        self::assertResponseHasHeader('retry-after');
        $problem = $this->responseData($client);
        self::assertSame('rate_limit_exceeded', $problem['code']);
    }

    public function testApiResponsesIncludeDefensiveHeaders(): void
    {
        $client = self::createClient();
        $client->jsonRequest('POST', '/api/v1/auth/login', [
            'email' => 'headers-test@example.com',
            'password' => 'StrongPassword1',
        ]);

        self::assertResponseHeaderSame('cache-control', 'no-store, private');
        self::assertResponseHeaderSame('x-content-type-options', 'nosniff');
        self::assertResponseHeaderSame('x-frame-options', 'DENY');
        self::assertResponseHeaderSame('referrer-policy', 'no-referrer');
        self::assertResponseHeaderSame('permissions-policy', 'camera=(), geolocation=(), microphone=()');
        self::assertResponseHeaderSame('content-security-policy', "default-src 'none'; frame-ancestors 'none'");
    }

    public function testCorsAllowsConfiguredLocalOriginOnly(): void
    {
        $client = self::createClient();
        $client->request('OPTIONS', '/api/v1/auth/login', server: [
            'HTTP_ORIGIN' => 'http://localhost:3000',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);
        self::assertResponseHeaderSame('access-control-allow-origin', 'http://localhost:3000');

        $client->request('OPTIONS', '/api/v1/auth/login', server: [
            'HTTP_ORIGIN' => 'https://attacker.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);
        self::assertResponseNotHasHeader('access-control-allow-origin');
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
