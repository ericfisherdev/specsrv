<?php

namespace App\Tests;

class ApplicationAvailabilityTest extends AbstractWebTestCase
{
    public function testApiCsrfTokenEndpointIsAvailable(): void
    {
        $this->client->request('GET', '/api/v1/csrf-token');

        $this->assertResponseIsSuccessful();
    }
}
