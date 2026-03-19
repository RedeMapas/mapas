<?php
declare(strict_types=1);

namespace ActivityPub\Controllers;

use ActivityPub\ActorBuilder;
use MapasCulturais\App;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class ActivityPub extends \MapasCulturais\Controller
{
    private const CONTENT_TYPE_AP  = 'application/activity+json; charset=utf-8';
    private const CONTENT_TYPE_JRD = 'application/jrd+json; charset=utf-8';
    private const PAGE_SIZE        = 20;

    // -----------------------------------------------------------------------
    // WebFinger
    // -----------------------------------------------------------------------

    public function webfinger(ServerRequestInterface $request): ResponseInterface
    {
        $params   = $request->getQueryParams();
        $resource = trim($params['resource'] ?? '');

        if ($resource === '') {
            return $this->ap(['error' => 'resource parameter required'], 400, self::CONTENT_TYPE_JRD);
        }

        if (!str_starts_with($resource, 'acct:')) {
            return $this->ap(['error' => 'Actor not found'], 404, self::CONTENT_TYPE_JRD);
        }

        [$slug, $incomingDomain] = array_pad(explode('@', substr($resource, 5), 2), 2, '');
        $domain = $this->domain();

        if ($incomingDomain !== $domain) {
            return $this->ap(['error' => 'Actor not found'], 404, self::CONTENT_TYPE_JRD);
        }

        if (!$this->findAgent($slug)) {
            return $this->ap(['error' => 'Actor not found'], 404, self::CONTENT_TYPE_JRD);
        }

        return $this->ap([
            'subject' => "acct:{$slug}@{$domain}",
            'links'   => [[
                'rel'  => 'self',
                'type' => 'application/activity+json',
                'href' => "https://{$domain}/activitypub/agent/{$slug}",
            ]],
        ], 200, self::CONTENT_TYPE_JRD);
    }

    // -----------------------------------------------------------------------
    // Actor
    // -----------------------------------------------------------------------

    public function actor(ServerRequestInterface $request, string $slug): ResponseInterface
    {
        $agent = $this->findAgent($slug);
        if (!$agent) {
            return $this->ap(['error' => 'Actor not found'], 404);
        }

        return $this->ap(ActorBuilder::build($agent, $this->domain()));
    }

    // -----------------------------------------------------------------------
    // Inbox (stub)
    // -----------------------------------------------------------------------

    public function inbox(ServerRequestInterface $request, string $slug): ResponseInterface
    {
        if (!$this->findAgent($slug)) {
            return $this->ap(['error' => 'Actor not found'], 404);
        }

        return $this->ap([
            '@context'     => 'https://www.w3.org/ns/activitystreams',
            'type'         => 'OrderedCollection',
            'totalItems'   => 0,
            'orderedItems' => [],
        ]);
    }

    // -----------------------------------------------------------------------
    // Outbox
    // -----------------------------------------------------------------------

    public function outbox(ServerRequestInterface $request, string $slug): ResponseInterface
    {
        $agent = $this->findAgent($slug);
        if (!$agent) {
            return $this->ap(['error' => 'Actor not found'], 404);
        }

        $params    = $request->getQueryParams();
        $page      = isset($params['page']) ? max(1, (int) $params['page']) : null;
        $domain    = $this->domain();
        $outboxUri = "https://{$domain}/activitypub/agent/{$slug}/outbox";

        $app   = App::i();
        $conn  = $app->em->getConnection();
        $agentId = (int) $agent->id;

        $total = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM activitypub_activity WHERE agent_id = :id",
            ['id' => $agentId]
        );

        if ($page === null) {
            return $this->ap([
                '@context'   => 'https://www.w3.org/ns/activitystreams',
                'type'       => 'OrderedCollection',
                'id'         => $outboxUri,
                'totalItems' => $total,
                'first'      => "{$outboxUri}?page=1",
            ]);
        }

        // Usar inteiros interpolados — DBAL PDO não suporta named params para LIMIT/OFFSET
        $limit  = self::PAGE_SIZE;
        $offset = ($page - 1) * $limit;

        $rows = $conn->fetchAllAssociative(
            "SELECT payload FROM activitypub_activity
             WHERE agent_id = {$agentId}
             ORDER BY published DESC
             LIMIT {$limit} OFFSET {$offset}"
        );

        $items = array_map(fn($r) => json_decode($r['payload'], true), $rows);

        $result = [
            '@context'     => 'https://www.w3.org/ns/activitystreams',
            'type'         => 'OrderedCollectionPage',
            'id'           => "{$outboxUri}?page={$page}",
            'partOf'       => $outboxUri,
            'orderedItems' => $items,
        ];

        if ($page > 1) {
            $result['prev'] = "{$outboxUri}?page=" . ($page - 1);
        }

        $lastPage = max(1, (int) ceil($total / self::PAGE_SIZE));
        if ($page < $lastPage) {
            $result['next'] = "{$outboxUri}?page=" . ($page + 1);
        }

        return $this->ap($result);
    }

    // -----------------------------------------------------------------------
    // Activity individual
    // -----------------------------------------------------------------------

    public function activity(ServerRequestInterface $request, string $slug, string $hash): ResponseInterface
    {
        $agent = $this->findAgent($slug);
        if (!$agent) {
            return $this->ap(['error' => 'Actor not found'], 404);
        }

        $domain     = $this->domain();
        $activityId = "https://{$domain}/activitypub/agent/{$slug}/activities/{$hash}";
        $agentId    = (int) $agent->id;

        $app  = App::i();
        $conn = $app->em->getConnection();
        $row  = $conn->fetchAssociative(
            "SELECT payload FROM activitypub_activity
             WHERE activity_id = :aid AND agent_id = :agentid",
            ['aid' => $activityId, 'agentid' => $agentId]
        );

        if (!$row) {
            return $this->ap(['error' => 'Activity not found'], 404);
        }

        return $this->ap(json_decode($row['payload'], true));
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function findAgent(string $slug): ?object
    {
        $app   = App::i();
        $agent = $app->repo('Agent')->findOneBy(['slug' => $slug]);

        if (!$agent || ($agent->status ?? 0) < 1) {
            return null;
        }

        return $agent;
    }

    private function domain(): string
    {
        $app    = App::i();
        $domain = (string) ($app->config['activitypub.domain'] ?? '');
        if ($domain !== '') {
            return $domain;
        }
        return (string) parse_url((string) ($app->config['base.url'] ?? ''), PHP_URL_HOST);
    }

    private function ap(array $data, int $status = 200, string $ct = self::CONTENT_TYPE_AP): ResponseInterface
    {
        $response = new Response($status);
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', $ct);
    }
}
