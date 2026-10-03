<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AuditEventRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AuditEventRepository::class)]
#[ORM\Table(name: 'audit_event')]
#[ORM\Index(name: 'idx_audit_event_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_audit_event_type', columns: ['event_type'])]
class AuditEvent
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /** @param array<string, mixed> $context */
    public function __construct(
        #[ORM\Column(length: 80)] private string $eventType,
        #[ORM\Column(length: 36, nullable: true)] private ?string $actorId,
        #[ORM\Column(length: 180, nullable: true)] private ?string $actorEmail,
        #[ORM\Column(length: 36, nullable: true)] private ?string $subjectId,
        #[ORM\Column(length: 180, nullable: true)] private ?string $subjectEmail,
        #[ORM\Column(length: 36)] private string $requestId,
        #[ORM\Column(length: 45, nullable: true)] private ?string $ipAddress,
        #[ORM\Column(type: 'json')] private array $context,
        #[ORM\Column] private \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getActorId(): ?string
    {
        return $this->actorId;
    }

    public function getActorEmail(): ?string
    {
        return $this->actorEmail;
    }

    public function getSubjectId(): ?string
    {
        return $this->subjectId;
    }

    public function getSubjectEmail(): ?string
    {
        return $this->subjectEmail;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return $this->context;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
