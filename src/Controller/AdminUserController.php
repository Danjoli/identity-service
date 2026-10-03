<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\UpdateUserAuthorizationRequest;
use App\Entity\User;
use App\Exception\UserNotFound;
use App\Repository\UserRepository;
use App\Service\UpdateUserAuthorization;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class AdminUserController extends AbstractController
{
    #[Route('/api/v1/admin/users', name: 'api_v1_admin_users_list', methods: ['GET'], format: 'json')]
    public function list(UserRepository $users): JsonResponse
    {
        return $this->json([
            'data' => array_map(self::userData(...), $users->findBy([], ['createdAt' => 'DESC'])),
        ]);
    }

    #[Route('/api/v1/admin/users/{id}', name: 'api_v1_admin_users_update', methods: ['PATCH'], format: 'json')]
    public function update(
        string $id,
        #[MapRequestPayload(acceptFormat: 'json')]
        UpdateUserAuthorizationRequest $request,
        UserRepository $users,
        UpdateUserAuthorization $updateUserAuthorization,
    ): JsonResponse {
        if (!Uuid::isValid($id) || !$target = $users->find($id)) {
            throw new UserNotFound();
        }

        $administrator = $this->getUser();
        if (!$administrator instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $user = $updateUserAuthorization->update($administrator, $target, $request);

        return $this->json(['data' => self::userData($user)]);
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
