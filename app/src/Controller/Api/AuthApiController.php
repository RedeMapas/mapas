<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Application\Environment;
use App\Exception\InvalidCredentialsException;
use App\Request\AuthRequest;
use App\Service\Interface\UserServiceInterface;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthApiController extends AbstractApiController
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly AuthRequest $authRequest
    ) {
    }

    public function auth(): JsonResponse
    {
        $request = $this->authRequest->validatePost();

        $user = $this->userService->findOneBy([
            'email' => $request['email'],
        ]);

        if (null === $user->getMetadata('localAuthenticationPassword')) {
            throw new InvalidCredentialsException();
        }

        if (false === password_verify($request['password'], $user->getMetadata('localAuthenticationPassword'))) {
            throw new InvalidCredentialsException();
        }

        $payload = [
            'email' => $request['email'],
        ];
        $jwt = JWT::encode($payload, Environment::getApiPrivateKey(), 'RS256');

        $user->setAuthToken($jwt);
        $this->userService->save($user);

        return new JsonResponse([
            'id' => $user->id,
            'name' => $user->profile->name,
            'token' => $user->getAuthToken(),
        ]);
    }
}
