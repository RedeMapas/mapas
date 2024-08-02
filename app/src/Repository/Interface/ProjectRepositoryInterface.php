<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use MapasCulturais\Entities\Project;

interface ProjectRepositoryInterface
{
    public function find(int $id): Project;

    public function findAll(): array;

    public function save(Project $project): void;

    public function remove(Project $project): void;
}
