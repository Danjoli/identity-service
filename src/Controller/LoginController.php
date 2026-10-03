<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\LoginRequest;
use App\Service\AuthenticateUser;
use App\Service\TokenPairIssuer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    #[Route('/api/v1/auth/login', name: 'api_v1_auth_login', methods: ['POST'], format: 'json')]
    public function __invoke(
        #[MapRequestPayload(acceptFormat: 'json')]
        LoginRequest $request,
        AuthenticateUser $authenticateUser,
        TokenPairIssuer $tokenPairIssuer,
    ): JsonResponse {
        $user = $authenticateUser->authenticate($request);

        return $this->json([
            'data' => [
                ...$tokenPairIssuer->issue($user),
            ],
        ]);
    }
}
