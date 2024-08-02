<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use MapasCulturais\Entities\Seal;

interface SealRepositoryInterface
{
    public function find(int $id): Seal;

    public function findAll(): array;

    public function save(Seal $seal): void;

    public function remove(Seal $seal): void;
}
