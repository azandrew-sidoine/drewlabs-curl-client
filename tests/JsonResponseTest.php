<?php


namespace Drewlabs\Curl\Tests;

use Drewlabs\Curl\Utils\JsonResponse;
use PHPUnit\Framework\TestCase;

class JsonResponseTest extends TestCase
{

    public function test_constructor_create_response_with_default_values()
    {
        $response = new JsonResponse();

        $this->assertTrue(is_array($response->getBody()));
        $this->assertEquals(0, count($response->getBody()));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
    }


    public function test_get_return_key_matching_provided_property_value_or_default_if_key_not_found()
    {
        $response = new JsonResponse([
            'name' => 'John Doe',
            'address' => [
                'email' => 'johndoe@example.com',
                'street' => '42 Martin Avenue'
            ]
        ]);

        $this->assertNull($response->get('ratings'));
        $this->assertEquals('None', $response->get('phonenumber', 'None'));
        $this->assertEquals('John Doe', $response->get('name'));
        $this->assertEquals('42 Martin Avenue', $response->get('address.street'));
    }
}