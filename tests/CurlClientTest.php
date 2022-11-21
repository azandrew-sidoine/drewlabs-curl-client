<?php

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseByMethod;
use donatj\MockWebServer\Responses\NotFoundResponse;
use Drewlabs\Curl\Client;
use Drewlabs\Curl\Mock\PostRequestResponse;
use PHPUnit\Framework\TestCase;

if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
    class CurlClientTest extends TestCase
    {
        /** @var MockWebServer */
        protected static $server;

        #[\ReturnTypeWillChange]
        public static function setUpBeforeClass(): void
        {
            self::$server = new MockWebServer;
            // The default response is donatj\MockWebServer\Responses\DefaultResponse
            // which returns an HTTP 200 and a descriptive JSON payload.
            //
            // Change the default response to donatj\MockWebServer\Responses\NotFoundResponse
            // to get a standard 404.
            //
            // Any other response may be specified as default as well.
            self::$server->setDefaultResponse(new NotFoundResponse);
            self::$server->start();

            return;
        }


        public function test_send_http_get_request()
        {
            $url = self::$server->setResponseOfPath('/test/post', new Response(json_encode([])));
            $client = new Client([
                'url' => str_replace('/test/post', '', $url),
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
        }

        public function test_curl_client_send_request_with_options()
        {
            $response = new ResponseByMethod([
                ResponseByMethod::METHOD_GET  => new Response("/GET response"),
                ResponseByMethod::METHOD_POST => new PostRequestResponse('', [], 201),
            ]);
            $url = self::$server->setResponseOfPath('/test/post/1', $response);
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
        }

        static function tearDownAfterClass(): void
        {
            // stopping the web server during tear down allows us to reuse the port for later tests
            self::$server->stop();

            return;
        }
    }
}
