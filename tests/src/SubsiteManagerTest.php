<?php

namespace Tests;

use Tests\Abstract\TestCase;
use MapasCulturais\Managers\SubsiteManager;

require_once __DIR__ . '/bootstrap.php';

class SubsiteManagerTest extends TestCase
{
    use Traits\UserDirector;
    use Traits\AgentDirector;

    public function testCanCreateSubsite()
    {
        $user = $this->userDirector->createUser();
        $agent = $this->agentDirector->createIndividual($user);
        $manager = new SubsiteManager($this->app);

        $data = [
            'name' => 'Test Subsite',
            'url' => 'test.local',
            'owner' => $agent->id,
            'namespace' => 'Subsite',
        ];

        $subsite = $manager->create($data);

        $this->assertInstanceOf('MapasCulturais\Entities\Subsite', $subsite);
        $this->assertEquals('Test Subsite', $subsite->name);
        $this->assertEquals('test.local', $subsite->url);
        $this->assertEquals(1, $subsite->status); // enabled
    }

    public function testCannotCreateSubsiteWithDuplicateUrl()
    {
        $user = $this->userDirector->createUser();
        $agent = $this->agentDirector->createIndividual($user);
        $manager = new SubsiteManager($this->app);

        // Create first subsite
        $manager->create([
            'name' => 'First',
            'url' => 'duplicate.local',
            'owner' => $agent->id,
            'namespace' => 'Subsite',
        ]);

        // Try to create duplicate
        $this->expectException(\Exception::class);
        $manager->create([
            'name' => 'Second',
            'url' => 'duplicate.local',
            'owner' => $agent->id,
            'namespace' => 'Subsite',
        ]);
    }
}