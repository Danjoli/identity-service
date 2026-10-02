<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\UpdateUserAuthorizationRequest;
use App\Entity\User;
use App\Enum\AccountStatus;
use App\Exception\SelfAuthorizationChange;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UpdateUserAuthorization
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function update(User $administrator, User $target, UpdateUserAuthorizationRequest $request): User
    {
        if ($administrator->getId()->equals($target->getId())) {
            throw new SelfAuthorizationChange();
        }

        $target->replaceRoles($request->roles);

        match (AccountStatus::from($request->status)) {
            AccountStatus::Active => $target->activate(),
            AccountStatus::Disabled => $target->disable(),
        };

        $this->entityManager->flush();

        return $target;
    }
}
