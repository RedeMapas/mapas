<?php

declare(strict_types=1);

use App\Controller\Api\AuthApiController;
use Symfony\Component\HttpFoundation\Request;

return [
    '/api/v2/auth' => [
        Request::METHOD_POST => [AuthApiController::class, 'auth'],
    ],
];
