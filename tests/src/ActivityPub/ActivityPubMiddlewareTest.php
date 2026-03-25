<?php
declare(strict_types=1);

namespace Tests\ActivityPub;

use ActivityPub\ActorBuilder;
use ActivityPub\Middleware\ActivityPubMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\Abstract\TestCase;
use Tests\Traits\UserDirector;

class ActivityPubMiddlewareTest extends TestCase
{
    use UserDirector;

    private function req(string $method, string $uri): ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest($method, $uri);
    }

    public function testPostInboxIsAcceptedByMiddleware(): void
    {
        $user  = $this->userDirector->createUser();
        $agent = $user->profile;
        $slug  = ActorBuilder::slugify((string) ($agent->name ?? ''));

        $middleware = new ActivityPubMiddleware();
        $request = $this->req('POST', "/activitypub/agent/{$slug}/inbox");
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(599);
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(202, $response->getStatusCode());
    }
}
