<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use MapasCulturais\Managers\SubsiteManager;

class SubsiteManagerTest extends TestCase
{
    public function testCanCreateSubsite()
    {
        $app = \MapasCulturais\App::i();
        $manager = new SubsiteManager($app);

        $data = [
            'name' => 'Test Subsite',
            'url' => 'test.local',
            'owner' => 1, // agent_id
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
        $app = \MapasCulturais\App::i();
        $manager = new SubsiteManager($app);

        // Create first subsite
        $manager->create([
            'name' => 'First',
            'url' => 'duplicate.local',
            'owner' => 1,
            'namespace' => 'Subsite',
        ]);

        // Try to create duplicate
        $this->expectException(\Exception::class);
        $manager->create([
            'name' => 'Second',
            'url' => 'duplicate.local',
            'owner' => 1,
            'namespace' => 'Subsite',
        ]);
    }
}
