<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ChangePasswordRequest;
use App\Dto\UpdateProfileRequest;
use App\Entity\User;
use App\Service\ChangePassword;
use App\Service\UpdateProfile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ProfileController extends AbstractController
{
    #[Route('/api/v1/me', name: 'api_v1_profile_show', methods: ['GET'], format: 'json')]
    public function show(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json(['data' => self::userData($user)]);
    }

    #[Route('/api/v1/me', name: 'api_v1_profile_update', methods: ['PATCH'], format: 'json')]
    public function update(
        #[CurrentUser] User $user,
        #[MapRequestPayload(acceptFormat: 'json')]
        UpdateProfileRequest $request,
        UpdateProfile $updateProfile,
    ): JsonResponse {
        return $this->json(['data' => self::userData($updateProfile->update($user, $request))]);
    }

    #[Route('/api/v1/me/password', name: 'api_v1_profile_password', methods: ['PUT'], format: 'json')]
    public function changePassword(
        #[CurrentUser] User $user,
        #[MapRequestPayload(acceptFormat: 'json')]
        ChangePasswordRequest $request,
        ChangePassword $changePassword,
    ): JsonResponse {
        $changePassword->change($user, $request);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /** @return array{id: string, email: string, displayName: string, roles: list<string>, status: string, createdAt: string, updatedAt: string} */
    private static function userData(User $user): array
    {
        return [
            'id' => $user->getId()->toRfc4122(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
            'roles' => $user->getRoles(),
            'status' => $user->getStatus()->value,
            'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $user->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
