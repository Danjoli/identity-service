<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\RefreshTokenRequest;
use App\Exception\AccountDisabled;
use App\Exception\InvalidRefreshToken;
use App\Service\TokenPairIssuer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RefreshTokenController extends AbstractController
{
    #[Route('/api/v1/auth/refresh', name: 'api_v1_auth_refresh', methods: ['POST'], format: 'json')]
    public function __invoke(
        #[MapRequestPayload(acceptFormat: 'json')]
        RefreshTokenRequest $request,
        TokenPairIssuer $tokenPairIssuer,
    ): JsonResponse {
        try {
            $tokens = $tokenPairIssuer->rotate($request->refreshToken);
        } catch (InvalidRefreshToken $exception) {
            return $this->json([
                'error' => ['code' => 'invalid_refresh_token', 'message' => $exception->getMessage()],
            ], JsonResponse::HTTP_UNAUTHORIZED);
        } catch (AccountDisabled $exception) {
            return $this->json([
                'error' => ['code' => 'account_disabled', 'message' => $exception->getMessage()],
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        return $this->json(['data' => $tokens]);
    }
}
