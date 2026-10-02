<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\RefreshTokenRequest;
use App\Entity\User;
use App\Service\RefreshTokenService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class LogoutController
{
    #[Route('/api/v1/auth/logout', name: 'api_v1_auth_logout', methods: ['POST'], format: 'json')]
    public function __invoke(
        #[CurrentUser]
        User $user,
        #[MapRequestPayload(acceptFormat: 'json')]
        RefreshTokenRequest $request,
        RefreshTokenService $refreshTokens,
    ): JsonResponse {
        $refreshTokens->revoke($request->refreshToken, $user);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
