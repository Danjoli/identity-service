<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ApiDocumentationController
{
    private const ASSETS = [
        'favicon-32x32.png',
        'swagger-ui-bundle.js',
        'swagger-ui-standalone-preset.js',
        'swagger-ui.css',
    ];

    private const ASSET_CONTENT_TYPES = [
        'favicon-32x32.png' => 'image/png',
        'swagger-ui-bundle.js' => 'application/javascript; charset=UTF-8',
        'swagger-ui-standalone-preset.js' => 'application/javascript; charset=UTF-8',
        'swagger-ui.css' => 'text/css; charset=UTF-8',
    ];

    public function __construct(
        private bool $enabled,
        private string $projectDir,
    ) {
    }

    #[Route('/docs', name: 'api_docs', methods: ['GET'])]
    public function index(): Response
    {
        $this->ensureEnabled();

        return new Response($this->html(), headers: [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'none'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; font-src 'self'; frame-ancestors 'none'",
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
        ]);
    }

    #[Route('/docs/openapi.yaml', name: 'api_docs_openapi', methods: ['GET'])]
    public function openApi(): BinaryFileResponse
    {
        $this->ensureEnabled();

        return new BinaryFileResponse(
            $this->projectDir.'/docs/openapi.yaml',
            headers: ['Content-Type' => 'application/yaml; charset=UTF-8', 'Cache-Control' => 'no-cache'],
        );
    }

    #[Route('/docs/assets/{asset}', name: 'api_docs_asset', methods: ['GET'])]
    public function asset(string $asset): BinaryFileResponse
    {
        $this->ensureEnabled();
        if (!in_array($asset, self::ASSETS, true)) {
            throw new NotFoundHttpException();
        }

        return new BinaryFileResponse(
            $this->projectDir.'/vendor/swagger-api/swagger-ui/dist/'.$asset,
            headers: [
                'Cache-Control' => 'public, max-age=31536000, immutable',
                'Content-Type' => self::ASSET_CONTENT_TYPES[$asset],
            ],
        );
    }

    private function ensureEnabled(): void
    {
        if (!$this->enabled) {
            throw new NotFoundHttpException();
        }
    }

    private function html(): string
    {
        return <<<'HTML'
            <!doctype html>
            <html lang="pt-BR">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Identity Service API</title>
                <link rel="icon" href="/docs/assets/favicon-32x32.png">
                <link rel="stylesheet" href="/docs/assets/swagger-ui.css">
            </head>
            <body>
                <div id="swagger-ui"></div>
                <script src="/docs/assets/swagger-ui-bundle.js"></script>
                <script src="/docs/assets/swagger-ui-standalone-preset.js"></script>
                <script>
                    window.ui = SwaggerUIBundle({
                        url: '/docs/openapi.yaml',
                        dom_id: '#swagger-ui',
                        deepLinking: true,
                        displayRequestDuration: true,
                        filter: true,
                        presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
                        layout: 'StandaloneLayout'
                    });
                </script>
            </body>
            </html>
            HTML;
    }
}
