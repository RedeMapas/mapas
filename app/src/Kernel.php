<?php

declare(strict_types=1);

namespace App;

use App\Exception\FieldInvalidException;
use App\Exception\FieldRequiredException;
use App\Exception\InvalidRequestException;
use App\Exception\RequiredIdParamException;
use App\Exception\ResourceNotFoundException as InternalResourceNotFoundException;
use App\Exception\ValidatorException;
use DI\ContainerBuilder;
use Error;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class Kernel
{
    private string $url;
    private RouteCollection $routes;

    public function __construct()
    {
        $this->url = $this->getPathRequest();
        $this->routes = require_once dirname(__DIR__).'/routes/routes.php';
    }

    public function execute(): void
    {
        try {
            $context = new RequestContext(
                method: $_SERVER['REQUEST_METHOD']
            );

            $matcher = new UrlMatcher($this->routes, $context);

            $this->dispatchAction($matcher);
        } catch (MethodNotAllowedException) {
            (new JsonResponse([
                'error' => 'Method not allowed: '.$_SERVER['REQUEST_METHOD'],
            ], status: Response::HTTP_METHOD_NOT_ALLOWED))->send();

            exit;
        } catch (ResourceNotFoundException) {
            return;
        }
    }

    private function getPathRequest(): string
    {
        return explode('?', $_SERVER['REQUEST_URI'])[0];
    }

    private function dispatchAction(UrlMatcher $matcher): void
    {
        $parameters = $matcher->match($this->url);

        $controller = array_shift($parameters);
        $method = array_shift($parameters);

        unset($parameters['_route']);

        $builder = new ContainerBuilder();
        $builder->addDefinitions(dirname(__DIR__).'/config/di.php');
        $container = $builder->build();

        $controller = $container->get($controller);

        try {
            $response = $controller->$method($parameters);
        } catch (InternalResourceNotFoundException $exception) {
            $response = new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (ValidatorException $exception) {
            $response = new JsonResponse([
                'error' => $exception->getMessage(),
                'fields' => $exception->getFields(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (FieldInvalidException|FieldRequiredException|RequiredIdParamException|InvalidRequestException $exception) {
            $response = new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception|Error $exception) {
            $response = new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        if ($response instanceof Response) {
            $response->send();
        }

        exit;
    }
}
