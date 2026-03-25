<?php
declare(strict_types=1);

namespace Tests\ActivityPub;

use ActivityPub\Backfill;
use ActivityPub\Jobs\RecordActivity;
use Tests\Abstract\TestCase;
use Tests\Builders\PhasePeriods\Open;
use Tests\Traits\EventDirector;
use Tests\Traits\OpportunityDirector;
use Tests\Traits\ProjectDirector;
use Tests\Traits\RegistrationDirector;
use Tests\Traits\SpaceDirector;
use Tests\Traits\UserDirector;

class BackfillTest extends TestCase
{
    use UserDirector;
    use EventDirector;
    use SpaceDirector;
    use ProjectDirector;
    use OpportunityDirector;
    use RegistrationDirector;

    public function testDryRunReportsEligibleEntitiesWithoutEnqueueingJobs(): void
    {
        $user = $this->userDirector->createUser();
        $agent = $user->profile;

        $this->eventDirector->createEvent($agent);
        $this->spaceDirector->createSpace($agent);
        $this->projectDirector->createProject($agent);
        $opportunity = $this->opportunityDirector->createOpportunity($agent, $agent, new Open());
        $this->registrationDirector->createSentRegistrations($opportunity, 1);

        $backfill = new Backfill($this->app);
        $report = $backfill->run([
            'dry-run' => true,
            'entity' => 'all',
            'include-registrations' => true,
        ]);

        $this->assertTrue($report['dryRun']);
        $this->assertSame(1, $report['totals']['Event']);
        $this->assertSame(1, $report['totals']['Space']);
        $this->assertSame(1, $report['totals']['Project']);
        $this->assertSame(1, $report['totals']['Opportunity']);
        $this->assertSame(1, $report['totals']['Registration']);
        $this->assertSame(0, $report['enqueued']['Event']);
        $this->assertSame(0, $report['enqueued']['Registration']);
        $this->assertSame(0, $this->countActivityJobs());
    }

    public function testBackfillEnqueuesAndProcessesActivitiesIdempotently(): void
    {
        $user = $this->userDirector->createUser();
        $agent = $user->profile;

        $event = $this->eventDirector->createEvent($agent);
        $space = $this->spaceDirector->createSpace($agent);
        $project = $this->projectDirector->createProject($agent);
        $opportunity = $this->opportunityDirector->createOpportunity($agent, $agent, new Open());
        $registration = $this->registrationDirector->createSentRegistrations($opportunity, 1)[0];

        $backfill = new Backfill($this->app);
        $report = $backfill->run([
            'entity' => 'all',
            'include-registrations' => true,
        ]);

        $this->assertFalse($report['dryRun']);
        $this->assertSame(1, $report['enqueued']['Event']);
        $this->assertSame(1, $report['enqueued']['Space']);
        $this->assertSame(1, $report['enqueued']['Project']);
        $this->assertSame(1, $report['enqueued']['Opportunity']);
        $this->assertSame(1, $report['enqueued']['Registration']);
        $this->assertSame(5, $this->countActivityJobs());

        $this->processJobs();

        $this->assertSame(0, $this->countActivityJobs());
        $this->assertSame(5, $this->countPersistedActivities());

        $rows = $this->fetchPersistedActivities();
        $this->assertArrayHasKey('Create:Event:' . $event->id, $rows);
        $this->assertArrayHasKey('Create:Space:' . $space->id, $rows);
        $this->assertArrayHasKey('Create:Project:' . $project->id, $rows);
        $this->assertArrayHasKey('Create:Opportunity:' . $opportunity->id, $rows);
        $this->assertArrayHasKey('Announce:Registration:' . $registration->id, $rows);

        $secondReport = $backfill->run([
            'entity' => 'all',
            'include-registrations' => true,
        ]);

        $this->assertSame(5, array_sum($secondReport['enqueued']));
        $this->assertSame(5, $this->countActivityJobs());

        $this->processJobs();

        $this->assertSame(5, $this->countPersistedActivities());
    }

    private function countActivityJobs(): int
    {
        return (int) $this->app->em->getConnection()->fetchOne(
            "SELECT COUNT(*) FROM job WHERE name = :name",
            ['name' => RecordActivity::SLUG]
        );
    }

    private function countPersistedActivities(): int
    {
        return (int) $this->app->em->getConnection()->fetchOne(
            "SELECT COUNT(*) FROM activitypub_activity"
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fetchPersistedActivities(): array
    {
        $rows = $this->app->em->getConnection()->fetchAllAssociative(
            "SELECT type, object_type, object_id FROM activitypub_activity"
        );

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[sprintf('%s:%s:%s', $row['type'], $row['object_type'], $row['object_id'])] = $row;
        }

        return $indexed;
    }
}
