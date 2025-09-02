<?php

namespace App\Tests\Integration;

use App\Tests\AbstractWebTestCase;

class AuthApiTest extends AbstractWebTestCase
{
    public function testLoginEndpoint(): void
    {
        // Create a test user first
        $user = $this->createTestUser([
            'email' => 'test@auth.com',
            'password' => 'password123',
        ]);

        $loginData = [
            'email' => 'test@auth.com',
            'password' => 'password123',
        ];

        $this->client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response: '.$response->getContent());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);

        $data = $responseData['data'];
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
        
        // Verify token is a string
        $this->assertIsString($data['token']);
        $this->assertNotEmpty($data['token']);

        // Verify user data structure
        $userData = $data['user'];
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('email', $userData);
        $this->assertEquals('test@auth.com', $userData['email']);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $loginData = [
            'email' => 'nonexistent@test.com',
            'password' => 'wrongpassword',
        ];

        $this->client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(401, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('INVALID_CREDENTIALS', $responseData['error']['code']);
    }

    public function testLoginWithMissingFields(): void
    {
        $loginData = [
            'email' => 'test@auth.com',
            // Missing password
        ];

        $this->client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('MISSING_CREDENTIALS', $responseData['error']['code']);
    }

    public function testRegisterEndpoint(): void
    {
        $registerData = [
            'email' => 'newuser@test.com',
            'password' => 'securepassword123',
            'name' => 'Test User',
        ];

        $this->client->request(
            'POST',
            '/api/v1/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($registerData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), 'Response: '.$response->getContent());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);

        $data = $responseData['data'];
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
        
        // Verify user data
        $userData = $data['user'];
        $this->assertEquals('newuser@test.com', $userData['email']);
        $this->assertEquals('Test User', $userData['name']);
    }

    public function testRegisterWithExistingEmail(): void
    {
        // Create an existing user
        $this->createTestUser(['email' => 'existing@test.com']);

        $registerData = [
            'email' => 'existing@test.com',
            'password' => 'password123',
        ];

        $this->client->request(
            'POST',
            '/api/v1/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($registerData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(409, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('USER_ALREADY_EXISTS', $responseData['error']['code']);
    }

    public function testRegisterWithInvalidData(): void
    {
        $registerData = [
            'email' => 'invalid-email', // Invalid email format
            'password' => '123', // Too short password
        ];

        $this->client->request(
            'POST',
            '/api/v1/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($registerData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        // Should have validation errors
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testRefreshEndpoint(): void
    {
        // Create user and get initial token
        $user = $this->createTestUser(['email' => 'refresh@test.com']);
        $apiKey = 'refresh-test-key';
        $this->createTestApiKey($user, ['keyHash' => hash('sha256', $apiKey)]);

        // Make authenticated request to refresh
        $this->makeAuthenticatedRequest('POST', '/api/v1/auth/refresh', $apiKey);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response: '.$response->getContent());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);

        $data = $responseData['data'];
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
    }

    public function testRefreshWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/v1/auth/refresh');

        $response = $this->client->getResponse();
        $this->assertEquals(401, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertNotNull($responseData, 'Response should be valid JSON: ' . $response->getContent());
        $this->assertArrayHasKey('success', $responseData, 'Response missing success field. Full response: ' . $response->getContent());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('AUTH_REQUIRED', $responseData['error']['code']);
    }

    public function testLogoutEndpoint(): void
    {
        // Create user and API key for authentication
        $user = $this->createTestUser(['email' => 'logout@test.com']);
        $apiKey = 'logout-test-key';
        $this->createTestApiKey($user, ['keyHash' => hash('sha256', $apiKey)]);

        $this->makeAuthenticatedRequest('POST', '/api/v1/auth/logout', $apiKey);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
    }

    public function testMeEndpoint(): void
    {
        $user = $this->createTestUser([
            'email' => 'me@test.com',
            'name' => 'Me Test User',
        ]);
        $apiKey = 'me-test-key';
        $this->createTestApiKey($user, ['keyHash' => hash('sha256', $apiKey)]);

        $this->makeAuthenticatedRequest('GET', '/api/v1/auth/me', $apiKey);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response: '.$response->getContent());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);

        $data = $responseData['data'];
        $this->assertArrayHasKey('user', $data);
        
        $userData = $data['user'];
        $this->assertEquals('me@test.com', $userData['email']);
        $this->assertEquals('Me Test User', $userData['name']);
    }

    public function testMeEndpointWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/v1/auth/me');

        $response = $this->client->getResponse();
        $this->assertEquals(401, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertNotNull($responseData, 'Response should be valid JSON: ' . $response->getContent());
        $this->assertArrayHasKey('success', $responseData, 'Response missing success field. Full response: ' . $response->getContent());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('AUTH_REQUIRED', $responseData['error']['code']);
    }

    public function testLoginPasswordHashing(): void
    {
        // Create user with hashed password
        $user = $this->createTestUser([
            'email' => 'hash@test.com',
            'password' => 'plaintextpassword',
        ]);

        // Verify we can login with the plain text password
        $loginData = [
            'email' => 'hash@test.com',
            'password' => 'plaintextpassword',
        ];

        $this->client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('token', $responseData['data']);
    }

    public function testRegisterPasswordHashing(): void
    {
        $registerData = [
            'email' => 'newhasheduser@test.com',
            'password' => 'plaintextpassword',
        ];

        $this->client->request(
            'POST',
            '/api/v1/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($registerData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());

        // Now try to login with the same credentials
        $loginData = [
            'email' => 'newhasheduser@test.com',
            'password' => 'plaintextpassword',
        ];

        $this->client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $loginResponse = $this->client->getResponse();
        $this->assertEquals(200, $loginResponse->getStatusCode());

        $loginResponseData = json_decode($loginResponse->getContent(), true);
        $this->assertTrue($loginResponseData['success']);
    }

    public function testInvalidJsonPayload(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }
}