<?php

class ApiTest extends PHPUnit_Framework_TestCase {

    protected $client;    

    protected function setUp(): void {
        parent::setUp();
        // Initialize HTTP client
        $this->client = new Client([
            'base_uri' => 'http://localhost/web_backend_test_catering_api', // Change to your API base URL
            'http_errors' => false // Prevent from throwing exceptions on HTTP errors
        ]);
    }

    public function testGetFacilityEndpoint(): void {
        // Send GET request to /Facility endpoint
        $response = $this->client->request('GET', '/facility');

        // Assert HTTP status code
        $this->assertEquals(200, $response->getStatusCode());

        // Assert response body or other properties as needed
        $responseData = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('facility', $responseData);
        // Add more assertions as needed
    }

}