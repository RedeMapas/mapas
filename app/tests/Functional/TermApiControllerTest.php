<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\AbstractTestCase;
use App\Tests\fixtures\TermTestFixtures;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TermApiControllerTest extends AbstractTestCase
{
    private const BASE_URL = '/api/v2/terms';

    public function testGetTermsShouldRetrieveAList(): void
    {
        $response = $this->client->request(Request::METHOD_GET, self::BASE_URL);
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsArray($content);

        $this->assertArrayHasKey('id', $content[0]);
        $this->assertArrayHasKey('taxonomy', $content[0]);

        $this->assertEquals(1, $content[0]['id']);
        $this->assertEquals('area', $content[0]['taxonomy']);
    }

    public function testGetOneTermShouldRetrieveAObject(): void
    {
        $response = $this->client->request(Request::METHOD_GET, self::BASE_URL.'/1');
        $content = json_decode($response->getContent());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsObject($content);

        $this->assertObjectHasProperty('@entityType', $content);
        $this->assertEquals('term', $content->{'@entityType'});
    }

    public function testGetOneTermShouldReturnNotFound(): void
    {
        $nonExistentId = 99999999;
        $response = $this->client->request(Request::METHOD_GET, self::BASE_URL.'/'.$nonExistentId);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testCreateTermShouldCreateANewTerm(): void
    {
        $termTestFixtures = TermTestFixtures::partial();

        $response = $this->client->request(Request::METHOD_POST, self::BASE_URL, [
            'body' => $termTestFixtures->json(),
        ]);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        foreach ($termTestFixtures->toArray() as $key => $value) {
            $this->assertEquals($value, $content[$key]);
        }
    }

    public function testDeleteTermShouldReturnNoContent(): void
    {
        $response = $this->client->request(Request::METHOD_DELETE, self::BASE_URL.'/1');

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testDeletedTermShouldReturnNotFound(): void
    {
        $this->client->request(Request::METHOD_DELETE, self::BASE_URL.'/202');

        $response = $this->client->request(Request::METHOD_DELETE, self::BASE_URL.'/202');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testTermUpdateShouldReturnUpdatedTerm(): void
    {
        $response = $this->client->request(Request::METHOD_PATCH, self::BASE_URL.'/2', [
            'body' => json_encode([
                'term' => 'Update term',
            ]),
        ]);

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Update term', $content['term']);
    }

    public function testUpdateATermThatNotExists(): void
    {
        $response = $this->client->request(Request::METHOD_PATCH, self::BASE_URL.'/999', [
            'body' => json_encode([
                'term' => 'Update',
            ]),
        ]);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @dataProvider termDataProvider
     */
    public function testTermValidations(array $termData, array $expectedMessages): void
    {
        $this->validateTerm($termData, $expectedMessages);
    }

    private function validateTerm(array $termData, array $expectedMessages): void
    {
        $response = $this->client->request(Request::METHOD_POST, self::BASE_URL, [
            'json' => $termData,
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

    public static function termDataProvider(): array
    {
        return [
            'blank fields' => [
                'termData' => [
                    'taxonomy' => '',
                    'term' => '',
                    'description' => '',
                ],
                'expectedMessages' => [
                    'This value should not be blank.',
                    'This value is too short. It should have 2 characters or more.',
                    'This value should not be blank.',
                    'This value is too short. It should have 2 characters or more.',
                    'This value should not be blank.',
                    'This value is too short. It should have 2 characters or more.',
                ],
            ],
            'invalid type fields' => [
                'termData' => [
                    'taxonomy' => 123,
                    'term' => 123,
                    'description' => 123,
                ],
                'expectedMessages' => [
                    'This value should be of type string.',
                    'This value should be of type string.',
                    'This value should be of type string.',
                ],
            ],
            'term already exists' => [
                'termData' => [
                    'taxonomy' => 'existing_taxonomy',
                    'term' => 'Arqueologia',
                    'description' => 'existing_description',
                ],
                'expectedMessages' => [
                    'This value is already used.',
                ],
            ],
        ];
    }
}
