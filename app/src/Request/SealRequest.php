<?php

declare(strict_types=1);

namespace App\Request;

use App\DTO\SealDto;
use App\Exception\ValidatorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SealRequest
{
    public function __construct(
        private readonly Request $request,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer
    ) {
    }

    public function validatePost(): array
    {
        $data = json_decode(
            json: $this->request->getContent(),
            associative: true
        );

        $seal = $this->serializer->denormalize($data, SealDto::class);

        $violations = $this->validator->validate($seal, groups: ['post']);

        if (0 < count($violations)) {
            throw new ValidatorException(violations: $violations);
        }

        return $data;
    }

    public function validatePatch(): array
    {
        $data = json_decode(
            json: $this->request->getContent(),
            associative: true
        );

        $seal = $this->serializer->denormalize($data, SealDto::class);

        $violations = $this->validator->validate($seal, groups: ['patch']);

        if (0 < count($violations)) {
            throw new ValidatorException(violations: $violations);
        }

        return $data;
    }
}
