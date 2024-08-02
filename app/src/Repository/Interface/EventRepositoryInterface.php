<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use MapasCulturais\Entities\Event;

interface EventRepositoryInterface
{
    public function find(int $id): Event;

    public function findAll(): array;

    public function findEventsBySpaceId(int $spaceId): array;

    public function save($event): void;

    public function update(Event $event): void;

    public function remove(Event $event): void;
}
