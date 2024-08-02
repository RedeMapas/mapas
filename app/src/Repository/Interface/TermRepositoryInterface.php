<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use MapasCulturais\Entities\Term;

interface TermRepositoryInterface
{
    public function find(int $id): Term;

    public function findAll(): array;

    public function save(Term $term): void;

    public function remove(Term $term): void;
}
