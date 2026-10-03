<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DatabaseReadinessCheck;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    #[Route('/health/live', name: 'health_live', methods: ['GET'], format: 'json')]
    public function live(): JsonResponse
    {
        return new JsonResponse(['status' => 'alive']);
    }

    #[Route('/health/ready', name: 'health_ready', methods: ['GET'], format: 'json')]
    public function ready(DatabaseReadinessCheck $database): JsonResponse
    {
        $ready = $database->isReady();

        return new JsonResponse(
            ['status' => $ready ? 'ready' : 'not_ready', 'checks' => ['database' => $ready ? 'up' : 'down']],
            $ready ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }
}
