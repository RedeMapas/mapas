<?php
declare(strict_types=1);

namespace Tests\ActivityPub;

use Tests\Abstract\TestCase;
use Tests\Traits\UserDirector;
use Slim\Psr7\Factory\ServerRequestFactory;
use ActivityPub\ActorBuilder;

class ActivityPubControllerTest extends TestCase
{
    use UserDirector;

    private function req(string $method, string $uri): \Psr\Http\Message\ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest($method, $uri);
    }

    private function ctrl(): \ActivityPub\Controllers\ActivityPub
    {
        return \ActivityPub\Controllers\ActivityPub::i('activitypub');
    }

    // --- WebFinger ---

    public function testWebFingerReturns400WithoutResource(): void
    {
        $resp = $this->ctrl()->webfinger($this->req('GET', '/.well-known/webfinger'));
        $this->assertSame(400, $resp->getStatusCode());
        $body = json_decode((string) $resp->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testWebFingerReturns404ForWrongDomain(): void
    {
        $req  = $this->req('GET', '/.well-known/webfinger?resource=acct:slug@wrong.domain');
        $resp = $this->ctrl()->webfinger($req->withQueryParams(['resource' => 'acct:slug@wrong.domain']));
        $this->assertSame(404, $resp->getStatusCode());
    }

    // --- Actor ---

    public function testActorReturns404ForUnknownSlug(): void
    {
        $resp = $this->ctrl()->actor($this->req('GET', '/x'), 'nonexistent-slug-xyz-987');
        $this->assertSame(404, $resp->getStatusCode());
    }

    public function testActorReturns200WithValidPersonPayload(): void
    {
        $user  = $this->userDirector->createUser();
        $agent = $user->profile;
        $slug  = ActorBuilder::slugify((string) ($agent->name ?? ''));

        $this->assertNotEmpty($slug, 'Agent deveria ter slug após save()');
        $resp = $this->ctrl()->actor($this->req('GET', "/activitypub/agent/{$slug}"), $slug);

        $this->assertSame(200, $resp->getStatusCode());
        $this->assertStringContainsString('activity+json', $resp->getHeaderLine('Content-Type'));

        $body = json_decode((string) $resp->getBody(), true);
        $this->assertSame('Person', $body['type']);
        $this->assertStringContainsString($slug, $body['id']);
        $this->assertArrayHasKey('outbox', $body);
        $this->assertArrayHasKey('inbox', $body);
        $this->assertArrayHasKey('publicKey', $body);
        $this->assertSame('', $body['publicKey']['publicKeyPem']);
    }

    // --- Outbox ---

    public function testOutboxReturnsOrderedCollection(): void
    {
        $user  = $this->userDirector->createUser();
        $agent = $user->profile;
        $slug  = ActorBuilder::slugify((string) ($agent->name ?? ''));

        $resp = $this->ctrl()->outbox($this->req('GET', "/activitypub/agent/{$slug}/outbox"), $slug);

        $this->assertSame(200, $resp->getStatusCode());
        $body = json_decode((string) $resp->getBody(), true);
        $this->assertSame('OrderedCollection', $body['type']);
        $this->assertArrayHasKey('totalItems', $body);
        $this->assertArrayHasKey('first', $body);
    }

    public function testOutboxPageReturnsOrderedCollectionPage(): void
    {
        $user  = $this->userDirector->createUser();
        $agent = $user->profile;
        $slug  = ActorBuilder::slugify((string) ($agent->name ?? ''));

        $req  = $this->req('GET', "/activitypub/agent/{$slug}/outbox?page=1")
                    ->withQueryParams(['page' => '1']);
        $resp = $this->ctrl()->outbox($req, $slug);

        $this->assertSame(200, $resp->getStatusCode());
        $body = json_decode((string) $resp->getBody(), true);
        $this->assertSame('OrderedCollectionPage', $body['type']);
        $this->assertArrayHasKey('orderedItems', $body);
        $this->assertArrayHasKey('partOf', $body);
        // Página 1 não tem 'prev'
        $this->assertArrayNotHasKey('prev', $body);
    }

    public function testOutboxReturns404ForUnknownSlug(): void
    {
        $resp = $this->ctrl()->outbox($this->req('GET', '/x'), 'nonexistent-slug-xyz-987');
        $this->assertSame(404, $resp->getStatusCode());
    }

    public function testInboxPostReturns202ForKnownActor(): void
    {
        $user  = $this->userDirector->createUser();
        $agent = $user->profile;
        $slug  = ActorBuilder::slugify((string) ($agent->name ?? ''));

        $resp = $this->ctrl()->inbox($this->req('POST', "/activitypub/agent/{$slug}/inbox"), $slug);

        $this->assertSame(202, $resp->getStatusCode());
        $body = json_decode((string) $resp->getBody(), true);
        $this->assertSame('accepted', $body['status']);
    }

    public function testWebFingerSelfLinkPreservesBaseUrlSchemeAndPort(): void
    {
        $user  = $this->userDirector->createUser();
        $agent = $user->profile;
        $slug  = ActorBuilder::slugify((string) ($agent->name ?? ''));

        $resource = "acct:{$slug}@localhost:8080";
        $req = $this->req('GET', "/.well-known/webfinger?resource={$resource}")
            ->withQueryParams(['resource' => $resource]);

        $resp = $this->ctrl()->webfinger($req);

        $this->assertSame(200, $resp->getStatusCode());

        $body = json_decode((string) $resp->getBody(), true);
        $this->assertSame($resource, $body['subject']);
        $this->assertSame('http://localhost:8080/activitypub/agent/' . $slug, $body['links'][0]['href']);
    }
}
