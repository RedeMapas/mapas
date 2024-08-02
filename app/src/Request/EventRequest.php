<?php

declare(strict_types=1);

namespace App\Request;

use App\DTO\EventDto;
use App\Exception\ValidatorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EventRequest
{
    public const GROUP_POST = 'post';

    public const GROUP_PATCH = 'patch';

    public function __construct(
        private readonly Request $request,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function validatePost(): array
    {
        return $this->validateEvent(self::GROUP_POST);
    }

    public function validateUpdate(): array
    {
        return $this->validateEvent(self::GROUP_PATCH);
    }

    public function validateEvent(string $validateGroup): array
    {
        $data = json_decode(
            json: $this->request->getContent(),
            associative: true
        );

        $event = $this->serializer->denormalize($data, EventDto::class);

        $violations = $this->validator->validate($event, groups: [$validateGroup]);

        if (0 < count($violations)) {
            throw new ValidatorException(violations: $violations);
        }

        return $data;
    }
}
