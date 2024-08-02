<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\AbstractTestCase;
use App\Tests\fixtures\EventTestFixtures;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EventApiControllerTest extends AbstractTestCase
{
    private const BASE_URL = '/api/v2/events';

    public function testGetEventsShouldRetrieveAList(): void
    {
        $response = $this->client->request(Request::METHOD_GET, self::BASE_URL);
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsArray($content);

        $this->assertArrayHasKey('id', $content[0]);
        $this->assertArrayHasKey('name', $content[0]);

        $this->assertEquals(1, $content[0]['id']);
        $this->assertEquals('Evento de Cultura', $content[0]['name']);
    }

    public function testGetOneEventShouldRetrieveAObject(): void
    {
        $response = $this->client->request(Request::METHOD_GET, self::BASE_URL.'/1');
        $content = json_decode($response->getContent());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsObject($content);

        $this->assertObjectHasProperty('@entityType', $content);
        $this->assertEquals('event', $content->{'@entityType'});
    }

    public function testGetOneEventShouldReturnNotFound(): void
    {
        $nonExistentId = 99999999;
        $response = $this->client->request(Request::METHOD_GET, self::BASE_URL.'/'.$nonExistentId);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testCreateEventShouldCreateANewEvent(): void
    {
        $eventTestFixtures = EventTestFixtures::partial();

        $response = $this->client->request(Request::METHOD_POST, self::BASE_URL, [
            'body' => $eventTestFixtures->json(),
        ]);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testUpdateEventShouldUpdateAnEvent(): void
    {
        $eventTestFixtures = EventTestFixtures::partial();

        $response = $this->client->request(Request::METHOD_PATCH, self::BASE_URL.'/1', [
            'body' => $eventTestFixtures->json(),
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testDeleteEventShouldReturnNoContent(): void
    {
        $response = $this->client->request(Request::METHOD_DELETE, self::BASE_URL.'/1');

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testDeleteEventShouldReturnNotFound(): void
    {
        $nonExistentId = 99999999;
        $response = $this->client->request(Request::METHOD_DELETE, self::BASE_URL.'/'.$nonExistentId);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @dataProvider eventDataProvider
     */
    public function testEventValidations(array $eventData, array $expectedMessages): void
    {
        $this->validateEvent($eventData, $expectedMessages);
    }

    private function validateEvent(array $eventData, array $expectedMessages): void
    {
        $response = $this->client->request(Request::METHOD_POST, self::BASE_URL, [
            'json' => $eventData,
        ]);

        $statusCode = $response->getStatusCode();
        $responseData = $response->toArray(false);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $statusCode);
        $this->assertEquals('The provided data violates one or more constraints.', $responseData['error']);

        $actualMessages = array_map(function ($fieldError) {
            return $fieldError['message'];
        }, $responseData['fields']);

        $this->assertEquals($expectedMessages, $actualMessages);
    }

    public static function eventDataProvider(): array
    {
        return [
            'blank fields' => [
                'eventData' => [
                    'name' => '',
                    'shortDescription' => '',
                    'classificacaoEtaria' => '',
                    'terms' => [
                        'linguagem' => [],
                    ],
                ],
                'expectedMessages' => [
                    'This value should not be blank.',
                    'This value is too short. It should have 2 characters or more.',
                    'This value should not be blank.',
                    'This value is too short. It should have 2 characters or more.',
                    'This value should not be blank.',
                    'This value is too short. It should have 2 characters or more.',
                    'This value should not be blank.',
                    'This collection should contain 1 element or more.',
                ],
            ],
            'invalid type fields' => [
                'eventData' => [
                    'name' => 123,
                    'shortDescription' => 123,
                    'classificacaoEtaria' => 123,
                    'terms' => [
                        'linguagem' => [123],
                        'tag' => [123],
                    ],
                ],
                'expectedMessages' => [
                    'This value should be of type string.',
                    'This value should be of type string.',
                    'This value should be of type string.',
                    'This value should be of type string.',
                    'This value should be of type string.',
                ],
            ],
            'missing required field linguagem' => [
                'eventData' => [
                    'name' => 'Evento Teste',
                    'shortDescription' => 'Descrição curta do evento',
                    'classificacaoEtaria' => 'livre',
                    'terms' => [
                        'linguagem' => [],
                    ],
                ],
                'expectedMessages' => [
                    'This value should not be blank.',
                    'This collection should contain 1 element or more.',
                ],
            ],
        ];
    }
}
