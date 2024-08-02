<?php

declare(strict_types=1);

namespace App\Service\Interface;

use MapasCulturais\Entities\Agent;

interface AgentServiceInterface
{
    public function create(mixed $data): Agent;

    public function removeById(int $id): void;

    public function getTypes(): array;

    public function update(int $id, object $data): Agent;
}
