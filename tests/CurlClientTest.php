<?php

namespace Drewlabs\Curl\Tests;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseByMethod;
use donatj\MockWebServer\Responses\NotFoundResponse;
use Drewlabs\Curl\Client;
use Drewlabs\Curl\Mock\PostRequestResponse;
use PHPUnit\Framework\TestCase;

class CurlClientTest extends TestCase
{


    public function runPHPUnitTest(\Closure $callback)
    {
        $server = new MockWebServer;
        // The default response is donatj\MockWebServer\Responses\DefaultResponse
        // which returns an HTTP 200 and a descriptive JSON payload.
        //
        // Change the default response to donatj\MockWebServer\Responses\NotFoundResponse
        // to get a standard 404.
        //
        // Any other response may be specified as default as well.
        $server->setDefaultResponse(new NotFoundResponse);
        $server->start();

        // Executes phpunit test
        $callback($server);

        $server->stop();
        
        return;
    }

    public function test_send_http_get_request()
    {
        $this->runPHPUnitTest(function ($server) {
            $url = $server->setResponseOfPath('/test/post', new Response(json_encode([]), [], 200));
            $client = new Client([
                'base_url' => str_replace('/test/post', '', $url),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => '*/*'
                ]
            ]);

            // Send the actual request to the server
            $client->send('GET', [
                'url' => '/test/post'
            ]);

            $response = $client->getResponse();
            $this->assertEquals(200, $client->getStatusCode());
            $this->assertTrue(is_string($response));
        });
    }


    public function test_client_release_reset_client_configurations_to_default()
    {
        $this->runPHPUnitTest(function ($server) {
            $url = $server->setResponseOfPath('/test/post', new ResponseByMethod([
                ResponseByMethod::METHOD_POST => new Response(json_encode([]), [], 200),
            ]));
            $client = new Client([
                'base_url' => str_replace('/test/post', '', $url),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => '*/*'
                ]
            ]);

            $client->setRequestMethod('POST')->send([ 'url' => '/test/post' ]);

            $this->assertEquals(200, $client->getStatusCode());
            $this->assertTrue(is_string($client->getResponse()));
            $this->assertTrue(is_string($client->getResponseHeaders()));

            $client->release();

            $this->assertTrue(null === $client->getStatusCode());
            $this->assertTrue(null === $client->getResponse());
            $this->assertTrue(null === $client->getResponseHeaders());
            $this->assertEquals([
                'base_url' => str_replace('/test/post', '', $url),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => '*/*'
                ],
                'method' => 'POST'
            ], $client->getOptions());
        });
    }

    public function test_curl_client_send_request_with_options()
    {
        $this->runPHPUnitTest(function ($server) {
            $response = new ResponseByMethod([
                ResponseByMethod::METHOD_GET  => new Response("/GET response"),
                ResponseByMethod::METHOD_POST => new PostRequestResponse('', [], 201),
            ]);
            $url = $server->setResponseOfPath('/test/post/1', $response);
            $client = new Client($url);

            $client->send('POST', [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => [
                    'title' => 'Hello World!',
                    'likes' => 2
                ]
            ]);

            $response = $client->getResponse();
            $this->assertEquals(201, $client->getStatusCode());
            $this->assertTrue(is_string($response));
        });
    }
}
