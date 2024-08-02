<?php

declare(strict_types=1);

namespace App\Request;

use App\Exception\FieldRequiredException;
use App\Exception\InvalidRequestException;
use Symfony\Component\HttpFoundation\Request;

class OpportunityRequest
{
    public function __construct(
        private readonly Request $request
    ) {
    }

    public function validatePost(): array
    {
        $jsonData = $this->request->getContent();
        $data = json_decode($jsonData, true);

        $requiredFields = ['objectType', 'name', 'terms', 'type'];

        foreach ($requiredFields as $field) {
            if (false === isset($data[$field]) || true === empty($data[$field])) {
                throw new FieldRequiredException(ucfirst($field));
            }
        }

        $linkFields = ['project', 'event', 'space', 'agent'];
        foreach ($linkFields as $field) {
            if (true === isset($data[$field]) && false === is_array($data[$field])) {
                throw new InvalidRequestException(ucfirst($field).' must be an array if provided.');
            }
        }

        return $data;
    }

    public function validateUpdate(): array
    {
        return json_decode(
            json: $this->request->getContent(),
            associative: true
        );
    }
}
