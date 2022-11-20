<?php

namespace Drewlabs\Curl;

class CurlHeadersCallback
{
    /**
     * 
     * @var array
     */
    private $cookies = [];

    /**
     * 
     * @var string
     */
    private $headers = '';

    /**
     * Curl rerquest header listener
     * 
     * @param mixed $curl 
     * @param mixed $header 
     * @return int 
     */
    public function __invoke($curl, $header)
    {
        if (null === $this->headers) {
            $this->headers = '';
        }
        if (null === $this->cookies) {
            $this->cookies = [];
        }
        if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]+)/mi', $header, $cookie) === 1) {
            $this->cookies[$cookie[1]] = trim($cookie[2], " \n\r\t\0\x0B");
        }
        $this->headers .= $header;
        return strlen($header);
    }

    /**
     * Returns the list of reponse cookies
     * 
     * @return array 
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Returns the raw response headers
     * 
     * @return string 
     */
    public function getHeaders()
    {
        return $this->headers ?? '';
    }

    public function __destruct()
    {
        $this->cookies = null ;
        $this->headers = null;
    }
}