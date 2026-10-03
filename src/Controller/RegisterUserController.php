<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\RegisterUserRequest;
use App\Service\EmailVerificationService;
use App\Service\RegisterUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterUserController extends AbstractController
{
    #[Route('/api/v1/auth/register', name: 'api_v1_auth_register', methods: ['POST'], format: 'json')]
    public function __invoke(
        #[MapRequestPayload(acceptFormat: 'json')]
        RegisterUserRequest $request,
        RegisterUser $registerUser,
        EmailVerificationService $emailVerification,
    ): JsonResponse {
        $user = $registerUser->register($request);

        $emailVerification->issueAndSend($user);

        return $this->json([
            'data' => [
                'id' => $user->getId()->toRfc4122(),
                'email' => $user->getEmail(),
                'displayName' => $user->getDisplayName(),
                'roles' => $user->getRoles(),
                'status' => $user->getStatus()->value,
                'emailVerifiedAt' => null,
                'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
            ],
        ], JsonResponse::HTTP_CREATED);
    }
}
