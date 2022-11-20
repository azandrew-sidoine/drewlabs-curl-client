<?php

use Drewlabs\Curl\ClientOptions;
use Drewlabs\Curl\CookiesBag;
use Drewlabs\Curl\RequestOptions;
use PHPUnit\Framework\TestCase;

class ClientOptionsTest extends TestCase
{

    public function test_client_options_static_create()
    {
        $options = ClientOptions::create([]);
        $this->assertInstanceOf(ClientOptions::class, $options);
    }


    public function test_client_options_create_with_attributes()
    {
        $options = ClientOptions::create([
            'verify' => false,
            'sink' => null,
            'force_resolve_ip' => false,
            'proxy' => ['http://proxy.app-ip.com'],
            'cert' => null,
            'ssl_key' => ['/home/webhost/.ssh/pub.key'],
            'progress' => new class {
                // Declare the function to handle the progress event
                public function __invoke()
                {
                    // Handle the progress event
                }
            },
            'base_url' => 'http://127.0.0.1:3000',
            'connect_timeout' => 1000,
            'request' => [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 10,
                'auth' => ['MyUser', 'MyPassword', 'digest'],
                'query' => [
                    'post_id' => 2, 'comments_count' => 1
                ],
                'encoding' => 'gzip,deflate'
            ],
            'cookies' => [
                'clientid' => 'myClientID', 'clientsecret' => 'MySuperSecret'
            ],
        ]);
        $this->assertEquals('http://127.0.0.1:3000', $options->getBaseURL());
        $this->assertEquals(['http://proxy.app-ip.com'], $options->getProxy());
        $this->assertInstanceOf(RequestOptions::class, $options->getRequest());
        $this->assertEquals(['Content-Type' => 'application/json'], $options->getRequest()->getHeaders());
        $this->assertEquals(10, $options->getRequest()->getTimeout());
        $this->assertEquals(['MyUser', 'MyPassword', 'digest'], $options->getRequest()->getAuth());
        $this->assertEquals(['post_id' => 2, 'comments_count' => 1], $options->getRequest()->getQuery());
        $this->assertEquals('gzip,deflate', $options->getRequest()->getEncoding());
        $this->assertInstanceOf(CookiesBag::class, $options->getCookies());
        $this->assertEquals('myClientID', $options->getCookies()->get('clientid'));
        $this->assertEquals('MySuperSecret', $options->getCookies()->get('clientsecret'));
        $this->assertEquals(false, $options->getForceResolveIp());
        $this->assertEquals(false, $options->getVerify());
    }
}
