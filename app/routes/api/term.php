<?php

declare(strict_types=1);

use App\Controller\Api\TermApiController;
use Symfony\Component\HttpFoundation\Request;

return [
    '/api/v2/terms' => [
        Request::METHOD_GET => [TermApiController::class, 'getList'],
        Request::METHOD_POST => [TermApiController::class, 'post'],
    ],
    '/api/v2/terms/{id}' => [
        Request::METHOD_GET => [TermApiController::class, 'getOne'],
        Request::METHOD_PATCH => [TermApiController::class, 'patch'],
        Request::METHOD_DELETE => [TermApiController::class, 'remove'],
    ],
];
