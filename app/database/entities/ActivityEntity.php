<?php

declare(strict_types=1);

namespace App\Entities;

use DateTimeImmutable;

class ActivityEntity
{
    private int                $id;
    private int                $tenantId;
    private int                $budgetId;
    private int                $userId;
    private string             $actionType;
    private string             $entityType;
    private ?int               $entityId;
    private string             $description;
    private array              $metadata;
    private ?DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    public function __construct(
        int $id,
        int $tenantId,
        int $budgetId,
        int $userId,
        string $actionType,
        string $entityType,
        ?int $entityId,
        string $description,
        array $metadata = [],
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        $this->id          = $id;
        $this->tenantId    = $tenantId;
        $this->budgetId    = $budgetId;
        $this->userId      = $userId;
        $this->actionType  = $actionType;
        $this->entityType  = $entityType;
        $this->entityId    = $entityId;
        $this->description = $description;
        $this->metadata    = $metadata;
        $this->createdAt   = $createdAt;
        $this->updatedAt   = $updatedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getBudgetId(): int
    {
        return $this->budgetId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function setActionType( string $actionType ): void
    {
        $this->actionType = $actionType;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType( string $entityType ): void
    {
        $this->entityType = $entityType;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId( ?int $entityId ): void
    {
        $this->entityId = $entityId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription( string $description ): void
    {
        $this->description = $description;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata( array $metadata ): void
    {
        $this->metadata = $metadata;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

}