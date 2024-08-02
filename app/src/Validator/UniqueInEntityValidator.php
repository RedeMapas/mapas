<?php

declare(strict_types=1);

namespace App\Validator;

use App\Validator\Constraints\UniqueInEntity;
use Doctrine\ORM\EntityManagerInterface;
use MapasCulturais\App;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueInEntityValidator extends ConstraintValidator
{
    private EntityManagerInterface $entity;

    public function __construct()
    {
        $app = App::i();
        $this->entity = $app->em;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (false === $constraint instanceof UniqueInEntity) {
            throw new UnexpectedTypeException($constraint, UniqueInEntity::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $result = $this->entity->getRepository($constraint->entity)->findBy([$constraint->field => $value]);

        if ([] === $result) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
