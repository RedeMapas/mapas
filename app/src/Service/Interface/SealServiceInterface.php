<?php

declare(strict_types=1);

namespace App\Service\Interface;

use MapasCulturais\Entities\Seal;

interface SealServiceInterface
{
    public function create(mixed $data): Seal;

    public function removeById(int $id): void;

    public function update(int $id, object $data): Seal;
}
