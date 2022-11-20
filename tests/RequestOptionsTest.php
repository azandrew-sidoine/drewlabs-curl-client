<?php

use Drewlabs\Curl\RequestOptions;
use PHPUnit\Framework\TestCase;

class RequestOptionsTest extends TestCase
{

    public function test_request_client_static_create()
    {
        $request = RequestOptions::create([]);

        $this->assertInstanceOf(RequestOptions::class, $request);
    }

    public function test_request_options_create_set_user_provided_attributes()
    {
        $request = RequestOptions::create([
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'timeout' => 10,
            'auth' => ['MyUser', 'MyPassword', 'digest'],
            'query' => [
                'post_id' => 2, 'comments_count' => 1
            ],
            'encoding' => 'gzip,deflate'
        ]);

        $this->assertEquals(['Content-Type' => 'application/json'], $request->getHeaders());
        $this->assertEquals(10, $request->getTimeout());
        $this->assertEquals(['MyUser', 'MyPassword', 'digest'], $request->getAuth());
        $this->assertEquals(['post_id' => 2, 'comments_count' => 1], $request->getQuery());
        $this->assertEquals('gzip,deflate', $request->getEncoding());
    }

    public function test_request_query_setters()
    {
        $request = new RequestOptions();
        $request->setAuth('MyUser', 'MyPassword', 'basic');
        $request->setHeaders(['Accept-Encoding' => 'gzip,deflate']);
        $request->setTimeout(12);

        $this->assertEquals(['Accept-Encoding' => 'gzip,deflate'], $request->getHeaders());
        $this->assertEquals(['MyUser', 'MyPassword', 'basic'], $request->getAuth());
        $this->assertEquals(12, $request->getTimeout());
    }

    public function test_request_option_set_headers_throughs_exception_for_non_dictionary_array()
    {
        $this->expectException(\InvalidArgumentException::class);
        $request = new RequestOptions();
        $request->setHeaders(['application/json', 'gzip,deflate']);
    }
}
