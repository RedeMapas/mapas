<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Validator\UniqueInEntityValidator;
use Symfony\Component\Validator\Constraint;

class UniqueInEntity extends Constraint
{
    public function __construct(
        public string $entity,
        public string $field,
        public string $message = 'This value is already used.',
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct($options, $groups, $payload);
    }

    public function validatedBy(): string
    {
        return UniqueInEntityValidator::class;
    }
}
