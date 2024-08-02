<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\DataFixtures\SealFixtures;
use App\Tests\AbstractTestCase;
use App\Tests\fixtures\SealTestFixtures;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SealApiControllerTest extends AbstractTestCase
{
    private const BASE_URL = '/api/v2/seals';

    public function testGetSealsShouldRetrieveAList(): void
    {
        $response = $this->client->request(Request::METHOD_GET, self::BASE_URL);
        $content = json_decode($response->getContent());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsArray($content);
    }

    public function testGetOneSealShouldRetrieveAObject(): void
    {
        $url = sprintf('%s/%s', self::BASE_URL, SealFixtures::SEAL_ID_4);

        $response = $this->client->request(Request::METHOD_GET, $url);
        $content = json_decode($response->getContent());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsObject($content);
    }

    public function testCreateSealShouldReturnCreatedSeal(): void
    {
        $sealTestFixtures = SealTestFixtures::partial();

        $response = $this->client->request(Request::METHOD_POST, self::BASE_URL, [
            'body' => $sealTestFixtures->json(),
        ]);

        $content = json_decode($response->getContent(), true);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertIsArray($content);
        foreach ($sealTestFixtures->toArray() as $key => $value) {
            $this->assertEquals($value, $content[$key]);
        }
    }

    public function testDeleteSealShouldReturnSuccess(): void
    {
        $url = sprintf('%s/%s', self::BASE_URL, SealFixtures::SEAL_ID_3);

        $response = $this->client->request(Request::METHOD_DELETE, $url);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $response = $this->client->request(Request::METHOD_GET, $url);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testUpdate(): void
    {
        $sealTestFixtures = SealTestFixtures::partial();

        $url = sprintf('%s/%s', self::BASE_URL, SealFixtures::SEAL_ID_4);

        $response = $this->client->request(Request::METHOD_PATCH, $url, [
            'body' => $sealTestFixtures->json(),
        ]);

        $content = json_decode($response->getContent(), true);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertIsArray($content);
        foreach ($sealTestFixtures->toArray() as $key => $value) {
            $this->assertEquals($value, $content[$key]);
        }
    }

    public function testUpdateNotFoundedResource(): void
    {
        $sealTestFixtures = SealTestFixtures::partial();

        $url = sprintf('%s/%s', self::BASE_URL, 80);

        $response = $this->client->request(Request::METHOD_PATCH, $url, [
            'body' => $sealTestFixtures->json(),
        ]);

        $error = [
            'error' => 'The resource was not found.',
        ];

        $content = json_decode($response->getContent(false), true);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertIsArray($content);
        $this->assertEquals($error, $content);
    }
}
