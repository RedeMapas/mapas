<?php

declare(strict_types=1);

namespace App\Service\Interface;

use MapasCulturais\Entities\Project;

interface ProjectServiceInterface
{
    public function create(mixed $data): Project;

    public function removeById(int $id): void;

    public function update(int $id, object $data): Project;
}
