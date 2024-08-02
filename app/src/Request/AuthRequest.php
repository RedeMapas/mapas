<?php

declare(strict_types=1);

namespace App\Request;

use App\DTO\AuthDto;
use App\Exception\ValidatorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthRequest
{
    public const GROUP_POST = 'post';

    public function __construct(
        private readonly Request $request,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function validatePost(): array
    {
        return $this->validateAuth(self::GROUP_POST);
    }

    private function validateAuth(string $validatorGroup): array
    {
        $data = json_decode(
            json: $this->request->getContent(),
            associative: true
        );

        $auth = $this->serializer->denormalize($data, AuthDto::class);

        $violations = $this->validator->validate($auth, groups: [$validatorGroup]);

        if (0 < count($violations)) {
            throw new ValidatorException(violations: $violations);
        }

        return $data;
    }
}
