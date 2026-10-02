<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\LoginRequest;
use App\Exception\AccountDisabled;
use App\Exception\InvalidCredentials;
use App\Service\AuthenticateUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    public function __construct(private readonly int $tokenTtl)
    {
    }

    #[Route('/api/v1/auth/login', name: 'api_v1_auth_login', methods: ['POST'], format: 'json')]
    public function __invoke(
        #[MapRequestPayload(acceptFormat: 'json')]
        LoginRequest $request,
        AuthenticateUser $authenticateUser,
        JWTTokenManagerInterface $tokenManager,
    ): JsonResponse {
        try {
            $user = $authenticateUser->authenticate($request);
        } catch (InvalidCredentials $exception) {
            return $this->json([
                'error' => ['code' => 'invalid_credentials', 'message' => $exception->getMessage()],
            ], JsonResponse::HTTP_UNAUTHORIZED);
        } catch (AccountDisabled $exception) {
            return $this->json([
                'error' => ['code' => 'account_disabled', 'message' => $exception->getMessage()],
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        return $this->json([
            'data' => [
                'tokenType' => 'Bearer',
                'accessToken' => $tokenManager->create($user),
                'expiresIn' => $this->tokenTtl,
            ],
        ]);
    }
}
