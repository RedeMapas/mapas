<?php
namespace MapasCulturaisTests;

class AgendaSingleTest extends TestCase
{
    function testRoutes()
    {

        $this->markTestSkipped('disable due efficiency');
        foreach ([null, 'normal', 'admin', 'superAdmin'] as $role) {
            foreach (['Agent', 'Space', 'Project'] as $class) {
                $entities = $this->app->repo($class)->findAll();
                foreach ($entities as $entity) {
                    if ($entity->status > 0) {
                        $url = $entity->controller->createUrl('agendaSingle', [$entity->id]) . '?from=1900-01-01&to=2020-12-31';
                        $this->assertGet200($url, 'agenda single status code 200: ' . $url);
                    }
                }

            }
        }
    }
}
