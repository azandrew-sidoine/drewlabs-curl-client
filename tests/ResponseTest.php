<?php

use Drewlabs\Curl\Utils\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function test_constructor_create_response_with_default_values()
    {
        $response = new Response();

        $this->assertTrue(empty($response->getBody()));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
    }

    public function test_with_body_copy_existing_response_and_modify_new_response_body()
    {
        $response = new Response();

        $response2 = $response->withBody('Hello World');

        $this->assertTrue(empty($response->getBody()));
        $this->assertFalse(empty($response2->getBody()));
        $this->assertEquals('Hello World', $response2->getBody());
    }


    public function test_json_creates_an_instance_of_json_response()
    {
        $response = new Response('{"name": "John Doe", "body_count": 5}', 200, ['content-type' => 'application/json']);
        $jsonResponse = $response->json();

        $this->assertTrue(is_array($jsonResponse->getBody()));
        $this->assertTrue($jsonResponse->getBody()['name'] === 'John Doe');
        $this->assertEquals(5, $jsonResponse->getBody()['body_count']);
    }


    public function test_response_get_header_returns_null_if_header_does_not_exists_or_value_if_header_exists()
    {
        $response = new Response('{"name": "John Doe", "body_count": 5}', 200, ['content-type' => 'application/json']);
        $this->assertTrue(is_null($response->getHeader('http-cookie')));
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
    }


    public function test_response_with_added_headers()
    {
        $response = new Response('', 200, ['http-cookie' => 'max-age=12000']);
        $response = $response->withAddedHeader('http-cookie', 'name=johndoe');
        $this->assertEquals('max-age=12000,name=johndoe', $response->getHeader('http-cookie'));
    }
}