<?php

declare(strict_types=1);

namespace App\Service\Interface;

use MapasCulturais\Entities\Event;

interface EventServiceInterface
{
    public function create(mixed $data): Event;

    public function removeById(int $id): void;

    public function getTypes(): array;

    public function update(int $id, object $data): Event;
}
