<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AccountStatus;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'identity_user')]
#[ORM\UniqueConstraint(name: 'uniq_identity_user_email', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /** @var non-empty-string */
    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 120)]
    private string $displayName;

    #[ORM\Column(name: 'password_hash')]
    private string $passwordHash;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    #[ORM\Column(enumType: AccountStatus::class)]
    private AccountStatus $status = AccountStatus::Active;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $email, string $displayName, string $passwordHash)
    {
        $this->id = Uuid::v7();
        $this->email = self::normalizeEmail($email);
        $this->displayName = trim($displayName);
        $this->passwordHash = $passwordHash;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    public function getStatus(): AccountStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return AccountStatus::Active === $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @param list<string> $roles */
    public function replaceRoles(array $roles): void
    {
        $this->roles = array_values(array_unique($roles));
        $this->touch();
    }

    public function changePasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
        $this->touch();
    }

    public function disable(): void
    {
        $this->status = AccountStatus::Disabled;
        $this->touch();
    }

    public function activate(): void
    {
        $this->status = AccountStatus::Active;
        $this->touch();
    }

    public function eraseCredentials(): void
    {
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /** @return non-empty-string */
    public static function normalizeEmail(string $email): string
    {
        $normalized = mb_strtolower(trim($email));

        if ('' === $normalized) {
            throw new \InvalidArgumentException('E-mail cannot be empty.');
        }

        return $normalized;
    }
}
