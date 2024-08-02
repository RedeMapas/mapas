<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use MapasCulturais\Entities\Space;

interface SpaceRepositoryInterface
{
    public function find(int $id): Space;

    public function findAll(): array;

    public function save(Space $space): void;

    public function remove(Space $space): void;
}
