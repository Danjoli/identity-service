<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\EmailVerificationRequest;
use App\Dto\ResendEmailVerificationRequest;
use App\Exception\InvalidEmailVerificationToken;
use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class EmailVerificationController extends AbstractController
{
    #[Route('/api/v1/auth/email/verify', name: 'api_v1_email_verify', methods: ['POST'], format: 'json')]
    public function verify(
        #[MapRequestPayload(acceptFormat: 'json')]
        EmailVerificationRequest $request,
        EmailVerificationService $verification,
    ): JsonResponse {
        try {
            $user = $verification->confirm($request->token);
        } catch (InvalidEmailVerificationToken $exception) {
            return $this->json([
                'error' => [
                    'code' => 'invalid_email_verification_token',
                    'message' => $exception->getMessage(),
                ],
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'data' => [
                'email' => $user->getEmail(),
                'emailVerifiedAt' => $user->getEmailVerifiedAt()?->format(DATE_ATOM),
            ],
        ]);
    }

    #[Route('/api/v1/auth/email/resend', name: 'api_v1_email_resend', methods: ['POST'], format: 'json')]
    public function resend(
        #[MapRequestPayload(acceptFormat: 'json')]
        ResendEmailVerificationRequest $request,
        EmailVerificationService $verification,
    ): JsonResponse {
        $verification->requestForEmail($request->email);

        return $this->json([
            'data' => [
                'message' => 'If the account exists and is not verified, a verification e-mail has been sent.',
            ],
        ], JsonResponse::HTTP_ACCEPTED);
    }
}
