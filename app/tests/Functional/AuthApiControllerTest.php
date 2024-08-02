<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\AbstractTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthApiControllerTest extends AbstractTestCase
{
    private const BASE_URL = '/api/v2/auth';

    public function testAuthenticationWithoutValidUserShouldReturnNotFound(): void
    {
        $response = $this->client->request(Request::METHOD_POST, self::BASE_URL, [
            'json' => [
                'email' => 'chiquim@email.com',
                'password' => '12345678',
            ],
        ]);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testAuthenticationWithInvalidPasswordShouldReturnInvalidCredentials(): void
    {
        $response = $this->client->request(Request::METHOD_POST, self::BASE_URL, [
            'json' => [
                'email' => 'Admin@local',
                'password' => '12345678',
            ],
        ]);

        $responseData = $response->toArray(throw: false);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Invalid Credentials', $responseData['error']);
    }

    /**
     * @dataProvider userDataProvider
     */
    public function testUserValidations(array $userData, array $expectedMessages): void
    {
        $this->validateUser($userData, $expectedMessages);
    }

    private function validateUser(array $userData, array $expectedMessages): void
    {
        $response = $this->client->request(Request::METHOD_POST, self::BASE_URL, [
            'json' => $userData,
        ]);

        $statusCode = $response->getStatusCode();
        $responseData = $response->toArray(false);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $statusCode);
        $this->assertEquals('The provided data violates one or more constraints.', $responseData['error']);

        $actualMessages = array_map(
            fn ($fieldError) => $fieldError['message'],
            $responseData['fields']
        );

        $this->assertEquals($expectedMessages, $actualMessages);
    }

    public static function userDataProvider(): array
    {
        return [
            'blank fields' => [
                'userData' => [
                    'email' => '',
                    'password' => '',
                ],
                'expectedMessages' => [
                    'This value should not be blank.',
                    'This value should not be blank.',
                ],
            ],
            'invalid type fields' => [
                'userData' => [
                    'email' => 123,
                    'string' => 123,
                ],
                'expectedMessages' => [
                    'This value should be of type string.',
                    'This value should not be blank.',
                ],
            ],
        ];
    }
}
