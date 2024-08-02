<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use MapasCulturais\Entities\User;

interface UserRepositoryInterface
{
    public function find(int $id): User;

    public function findAll(): array;

    public function findBy(array $params): array;

    public function getUserMetadata(int $userId): mixed;

    public function findOneBy(array $params): ?User;

    public function save(User $user): void;
}
