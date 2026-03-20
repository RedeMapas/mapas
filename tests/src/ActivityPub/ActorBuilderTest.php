<?php
declare(strict_types=1);

namespace Tests\ActivityPub;

use PHPUnit\Framework\TestCase;
use ActivityPub\ActorBuilder;

class ActorBuilderTest extends TestCase
{
    private function makeAgent(): object
    {
        $agent = new \stdClass();
        $agent->id = 42;
        $agent->name = 'Maria Silva';
        $agent->shortDescription = 'Artista visual';
        $agent->status = 1;
        // Avatar como objeto File simulado
        $avatar = new \stdClass();
        $avatar->url = 'https://example.com/avatar.jpg';
        $agent->avatar = $avatar;
        $agent->singleUrl = 'https://example.com/agente/42';
        return $agent;
    }

    public function testActorHasRequiredFields(): void
    {
        $actor = ActorBuilder::build($this->makeAgent(), 'example.com');

        $this->assertSame('Person', $actor['type']);
        $this->assertSame('https://example.com/activitypub/agent/maria-silva', $actor['id']);
        $this->assertSame('maria-silva', $actor['preferredUsername']);
        $this->assertSame('Maria Silva', $actor['name']);
        $this->assertSame('Artista visual', $actor['summary']);
        $this->assertSame('https://example.com/activitypub/agent/maria-silva/outbox', $actor['outbox']);
        $this->assertSame('https://example.com/activitypub/agent/maria-silva/inbox', $actor['inbox']);
    }

    public function testActorHasPublicKeyStub(): void
    {
        $actor = ActorBuilder::build($this->makeAgent(), 'example.com');

        $this->assertArrayHasKey('publicKey', $actor);
        $this->assertSame('https://example.com/activitypub/agent/maria-silva#main-key', $actor['publicKey']['id']);
        $this->assertSame('https://example.com/activitypub/agent/maria-silva', $actor['publicKey']['owner']);
        $this->assertSame('', $actor['publicKey']['publicKeyPem']);
    }

    public function testActorContextIncludesSecurityVocab(): void
    {
        $actor = ActorBuilder::build($this->makeAgent(), 'example.com');

        $this->assertIsArray($actor['@context']);
        $this->assertContains('https://w3id.org/security/v1', $actor['@context']);
        $this->assertContains('https://www.w3.org/ns/activitystreams', $actor['@context']);
    }

    public function testActorIconFromAvatarObject(): void
    {
        $actor = ActorBuilder::build($this->makeAgent(), 'example.com');

        $this->assertSame('Image', $actor['icon']['type']);
        $this->assertSame('https://example.com/avatar.jpg', $actor['icon']['url']);
    }

    public function testActorWithoutAvatarHasNoIcon(): void
    {
        $agent = $this->makeAgent();
        $agent->avatar = null;

        $actor = ActorBuilder::build($agent, 'example.com');

        $this->assertArrayNotHasKey('icon', $actor);
    }
}
