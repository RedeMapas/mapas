<?php

declare(strict_types=1);

namespace App\Service\Interface;

use MapasCulturais\Entities\Opportunity;

interface OpportunityServiceInterface
{
    public function create(mixed $data): Opportunity;

    public function removeById(int $id): void;

    public function getTypes(): array;

    public function update(int $id, object $data): Opportunity;
}
