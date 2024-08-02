<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Exception\RequiredIdParamException;

abstract class AbstractApiController
{
    public function extractIdParam(array $params): int
    {
        if (false === isset($params['id'])) {
            throw new RequiredIdParamException();
        }

        return (int) $params['id'];
    }
}
