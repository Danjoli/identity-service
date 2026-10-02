<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\RegisterUserRequest;
use App\Entity\User;
use App\Exception\EmailAlreadyExists;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class RegisterUser
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $users,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function register(RegisterUserRequest $request): User
    {
        if (null !== $this->users->findOneByEmail($request->email)) {
            throw new EmailAlreadyExists();
        }

        $user = new User($request->email, $request->displayName, 'pending-hash');
        $user->changePasswordHash($this->passwordHasher->hashPassword($user, $request->password));

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw new EmailAlreadyExists();
        }

        return $user;
    }
}
