<?php

declare(strict_types=1);

namespace App\Tests;

class AgentApiControllerTest extends AbstractTestCase
{
    public function testGetAgentsShouldRetrieveAList(): void
    {
        $response = $this->client->request('GET', '/api/v2/agents');
        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($content);
    }

    public function testGetOneAgentShouldRetrieveAObject(): void
    {
        $response = $this->client->request('GET', '/api/v2/agents/1');
        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsObject($content);
    }

    public function testGetAgentTypesShouldRetrieveAList(): void
    {
        $response = $this->client->request('GET', '/api/v2/agents/types');
        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($content);
    }

    public function testGetAgentOpportunitiesShouldRetrieveAList(): void
    {
        $response = $this->client->request('GET', '/api/v2/agents/1/opportunities');
        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($content);
    }
}