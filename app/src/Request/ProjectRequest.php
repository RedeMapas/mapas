<?php

declare(strict_types=1);

namespace App\Request;

use App\Exception\FieldRequiredException;
use Symfony\Component\HttpFoundation\Request;

class ProjectRequest
{
    public function __construct(
        private readonly Request $request
    ) {
    }

    public function validatePost(): array
    {
        $jsonData = $this->request->getContent();
        $data = json_decode($jsonData, true);

        $requiredFields = ['name', 'shortDescription', 'type'];

        foreach ($requiredFields as $field) {
            if (false === isset($data[$field])) {
                throw new FieldRequiredException(ucfirst($field));
            }
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
