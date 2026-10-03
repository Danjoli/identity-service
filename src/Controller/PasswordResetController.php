<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\PasswordResetConfirmationRequest;
use App\Dto\PasswordResetRequest;
use App\Exception\InvalidPasswordResetToken;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PasswordResetController extends AbstractController
{
    #[Route('/api/v1/auth/password/request', name: 'api_v1_password_reset_request', methods: ['POST'], format: 'json')]
    public function request(
        #[MapRequestPayload(acceptFormat: 'json')]
        PasswordResetRequest $request,
        PasswordResetService $passwordReset,
    ): JsonResponse {
        $passwordReset->requestForEmail($request->email);

        return $this->json([
            'data' => [
                'message' => 'If the account is eligible, password reset instructions have been sent.',
            ],
        ], JsonResponse::HTTP_ACCEPTED);
    }

    #[Route('/api/v1/auth/password/reset', name: 'api_v1_password_reset_confirm', methods: ['POST'], format: 'json')]
    public function reset(
        #[MapRequestPayload(acceptFormat: 'json')]
        PasswordResetConfirmationRequest $request,
        PasswordResetService $passwordReset,
    ): JsonResponse {
        try {
            $passwordReset->reset($request->token, $request->newPassword);
        } catch (InvalidPasswordResetToken $exception) {
            return $this->json([
                'error' => [
                    'code' => 'invalid_password_reset_token',
                    'message' => $exception->getMessage(),
                ],
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
