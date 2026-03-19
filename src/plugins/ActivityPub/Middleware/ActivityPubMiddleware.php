<?php
declare(strict_types=1);

namespace ActivityPub\Middleware;

use ActivityPub\Controllers\ActivityPub as ActivityPubController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class ActivityPubMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path   = $request->getUri()->getPath();
        $method = strtoupper($request->getMethod());

        // WebFinger — apenas GET
        if ($path === '/.well-known/webfinger') {
            if ($method !== 'GET') {
                return $this->methodNotAllowed(['GET']);
            }
            return $this->ctrl()->webfinger($request);
        }

        // /activitypub/agent/{slug}[/sub-resource]
        if (str_starts_with($path, '/activitypub/agent/')) {
            $rest  = substr($path, strlen('/activitypub/agent/'));
            $parts = explode('/', trim($rest, '/'));
            $slug  = $parts[0] ?? '';

            if ($slug === '') {
                return $handler->handle($request);
            }

            $ctrl = $this->ctrl();

            // /activitypub/agent/{slug}  (somente GET)
            if (count($parts) === 1) {
                if ($method !== 'GET') {
                    return $this->methodNotAllowed(['GET']);
                }
                return $ctrl->actor($request, $slug);
            }

            $sub = $parts[1] ?? '';

            // /activitypub/agent/{slug}/outbox
            if ($sub === 'outbox') {
                if ($method !== 'GET') {
                    return $this->methodNotAllowed(['GET']);
                }
                return $ctrl->outbox($request, $slug);
            }

            // /activitypub/agent/{slug}/inbox
            if ($sub === 'inbox') {
                if ($method === 'GET') {
                    return $ctrl->inbox($request, $slug);
                }
                // POST e outros métodos → 405 (Inbox não implementado ainda)
                return $this->methodNotAllowed(['GET']);
            }

            // /activitypub/agent/{slug}/activities/{hash}
            if ($sub === 'activities' && isset($parts[2])) {
                if ($method !== 'GET') {
                    return $this->methodNotAllowed(['GET']);
                }
                return $ctrl->activity($request, $slug, $parts[2]);
            }
        }

        return $handler->handle($request);
    }

    private function ctrl(): ActivityPubController
    {
        return ActivityPubController::i();
    }

    private function methodNotAllowed(array $allowed): ResponseInterface
    {
        $response = new Response(405);
        return $response->withHeader('Allow', implode(', ', $allowed));
    }
}
