<?php

declare(strict_types=1);

namespace App\Request;

use App\Exception\FieldRequiredException;
use App\Exception\InvalidRequestException;
use Symfony\Component\HttpFoundation\Request;

class SpaceRequest
{
    public function __construct(
        private Request $request
    ) {
    }

    public function validatePost(): array
    {
        $jsonData = $this->request->getContent();
        $data = json_decode($jsonData, true);

        $requiredFields = ['name', 'terms', 'type', 'shortDescription'];

        foreach ($requiredFields as $field) {
            if (false === isset($data[$field])) {
                throw new FieldRequiredException(ucfirst($field));
            }
        }

        if (false === is_array($data['terms']) || false === is_array($data['terms']['area'])) {
            throw new InvalidRequestException('the terms field must be an object with a property "area" which is an array');
        }

        return $data;
    }

    public function validateUpdate(): array
    {
        $jsonData = $this->request->getContent();
        $data = json_decode($jsonData, true);

        return $data;
    }
}
