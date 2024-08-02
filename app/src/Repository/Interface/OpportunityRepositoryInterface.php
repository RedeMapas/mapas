<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use MapasCulturais\Entities\Opportunity;

interface OpportunityRepositoryInterface
{
    public function find(int $id): Opportunity;

    public function findAll(): array;

    public function findOpportunitiesByAgentId(int $agentId): array;

    public function save(Opportunity $opportunity): void;

    public function remove(Opportunity $opportunity): void;
}
