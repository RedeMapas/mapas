<?php

declare(strict_types=1);

namespace App\Service\Interface;

use MapasCulturais\Entities\Term;

interface TermServiceInterface
{
    public function create(mixed $data): Term;

    public function update(int $id, array $termData): Term;

    public function removeById(int $id): void;
}
