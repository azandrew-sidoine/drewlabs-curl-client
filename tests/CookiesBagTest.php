<?php

use Drewlabs\Curl\CookiesBag;
use PHPUnit\Framework\TestCase;

class CookiesBagTest extends TestCase
{
    public function test_cookies_constructor()
    {
        $cookies = new CookiesBag();
        $this->assertInstanceOf(CookiesBag::class, $cookies);
    }

    public function test_cookies_bag_set()
    {
        $cookies = new CookiesBag();
        $cookies->set('clientid', uniqid());
        $this->assertTrue($cookies->has('clientid'));
    }
    public function test_cookies_bag_remove_removes_cookie_from_the_bag()
    {
        $cookies = new CookiesBag();
        $cookies->set('clientid', uniqid());
        $cookies->set('clientsecret', 'MyAPISecretCookie');
        $this->assertTrue($cookies->has('clientid'));
        $cookies->remove('clientsecret');
        $this->assertFalse($cookies->has('clientsecret'));
    }
}