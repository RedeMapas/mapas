<?php
declare(strict_types=1);

namespace Tests\ActivityPub;

use PHPUnit\Framework\TestCase;
use ActivityPub\ActivityBuilder;

class ActivityBuilderTest extends TestCase
{
    private function makeActor(int $id = 42, string $name = 'Test Actor'): object
    {
        $a = new \stdClass();
        $a->id = $id;
        $a->name = $name;
        return $a;
    }

    private function makeEvent(): object
    {
        $e = new \stdClass();
        $e->id = 10;
        $e->name = 'Festival de Música';
        $e->shortDescription = 'Um evento incrível';
        $e->singleUrl = 'https://example.com/evento/10';
        $e->createTimestamp = new \DateTime('2026-01-15T10:00:00+00:00');
        $e->updateTimestamp = new \DateTime('2026-01-16T12:00:00+00:00');
        $e->occurrences = [];
        return $e;
    }

    private function makeSpace(): object
    {
        $s = new \stdClass();
        $s->id = 20;
        $s->name = 'Casa de Cultura';
        $s->shortDescription = 'Espaço cultural';
        $s->singleUrl = 'https://example.com/espaco/20';
        $s->createTimestamp = new \DateTime('2026-01-10T09:00:00+00:00');
        $s->updateTimestamp = new \DateTime('2026-01-11T09:00:00+00:00');
        $loc = new \stdClass();
        $loc->latitude = -23.5;
        $loc->longitude = -46.6;
        $s->location = $loc;
        return $s;
    }

    private function makeRegistration(): object
    {
        $r = new \stdClass();
        $r->id = 30;
        $r->createTimestamp = new \DateTime('2026-02-01T08:00:00+00:00');
        $r->updateTimestamp = new \DateTime('2026-02-01T08:00:00+00:00');
        $opp = new \stdClass();
        $opp->name = 'Edital de Cultura';
        $opp->singleUrl = 'https://example.com/oportunidade/5';
        $r->opportunity = $opp;
        return $r;
    }

    private function makeAgentRelation(): object
    {
        $ar = new \stdClass();
        $ar->id = 50;
        $ar->createTimestamp = new \DateTime('2026-02-10T10:00:00+00:00');
        $ar->updateTimestamp = new \DateTime('2026-02-10T10:00:00+00:00');
        $owner = new \stdClass();
        $owner->singleUrl = 'https://example.com/espaco/20';
        $ar->owner = $owner;
        return $ar;
    }

    private const ACT_ID = 'https://example.com/activitypub/agent/test-actor/activities/abc123';

    public function testCreateEventActivity(): void
    {
        $activity = ActivityBuilder::build('Create', $this->makeEvent(), 'MapasCulturais\Entities\Event', $this->makeActor(), 'example.com', self::ACT_ID);

        $this->assertSame('Create', $activity['type']);
        $this->assertSame(self::ACT_ID, $activity['id']);
        $this->assertSame('https://example.com/activitypub/agent/test-actor', $activity['actor']);
        $this->assertSame('Event', $activity['object']['type']);
        $this->assertSame('Festival de Música', $activity['object']['name']);
        $this->assertSame('https://example.com/evento/10', $activity['object']['url']);
        $this->assertSame('2026-01-15T10:00:00+00:00', $activity['published']); // createTimestamp
    }

    public function testUpdateSpaceActivity(): void
    {
        $activity = ActivityBuilder::build('Update', $this->makeSpace(), 'MapasCulturais\Entities\Space', $this->makeActor(), 'example.com', self::ACT_ID);

        $this->assertSame('Update', $activity['type']);
        $this->assertSame('Place', $activity['object']['type']);
        $this->assertSame('Casa de Cultura', $activity['object']['name']);
        $this->assertSame(-23.5, $activity['object']['latitude']);
        $this->assertSame(-46.6, $activity['object']['longitude']);
        $this->assertSame('2026-01-11T09:00:00+00:00', $activity['published']); // updateTimestamp
    }

    public function testAnnounceRegistrationActivity(): void
    {
        $activity = ActivityBuilder::build('Announce', $this->makeRegistration(), 'MapasCulturais\Entities\Registration', $this->makeActor(), 'example.com', self::ACT_ID);

        $this->assertSame('Announce', $activity['type']);
        $this->assertSame('Note', $activity['object']['type']);
        $this->assertStringContainsString('Edital de Cultura', $activity['object']['content']);
        $this->assertSame('https://example.com/oportunidade/5', $activity['object']['url']);
    }

    public function testAddAgentRelationActivity(): void
    {
        $activity = ActivityBuilder::build('Add', $this->makeAgentRelation(), 'MapasCulturais\Entities\AgentRelation', $this->makeActor(), 'example.com', self::ACT_ID);

        $this->assertSame('Add', $activity['type']);
        $this->assertSame('Relationship', $activity['object']['type']);
        $this->assertSame('https://example.com/activitypub/agent/test-actor', $activity['object']['subject']);
        $this->assertSame('administrator', $activity['object']['relationship']);
        $this->assertSame('https://example.com/espaco/20', $activity['object']['object']);
    }

    public function testActivityHasContext(): void
    {
        $activity = ActivityBuilder::build('Create', $this->makeEvent(), 'MapasCulturais\Entities\Event', $this->makeActor(), 'example.com', self::ACT_ID);

        $this->assertSame('https://www.w3.org/ns/activitystreams', $activity['@context']);
    }

    public function testObjectAttributedToActor(): void
    {
        $activity = ActivityBuilder::build('Create', $this->makeEvent(), 'MapasCulturais\Entities\Event', $this->makeActor(), 'example.com', self::ACT_ID);

        $this->assertSame('https://example.com/activitypub/agent/test-actor', $activity['object']['attributedTo']);
    }
}
