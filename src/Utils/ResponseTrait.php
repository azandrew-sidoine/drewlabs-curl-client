<?php

namespace Drewlabs\Curl\Utils;

/**
 * @method mixed getBody()
 */
trait ResponseTrait
{
    /** @var array<string,mixed> */
    private $headers = [];

    /** @var int */
    private $status;

    /**
     * returns the response status code
     * 
     * @return int 
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * returns the header value for the $name attribute
     * 
     * @param string $name 
     * @param mixed $default 
     * @return string 
     */
    public function getHeader(string $name, $default = null)
    {
        $default = !is_string($default) && is_callable($default) ? $default() : function () use ($default) {
            return $default ?? null;
        };
        if (empty($headers = $this->getHeaders())) {
            return $default();
        }
        $normalized = strtolower($name);
        foreach ($headers as $key => $header) {
            if (strtolower($key) === $normalized) {
                return implode(',', \is_array($header) ? $header : [$header]);
            }
        }
        return $default();
    }

    /**
     * returns the list of response headers
     * 
     * @return array 
     */
    public function getHeaders()
    {
        return $this->headers ?? [];
    }

    

    /**
     * immutable interface that modifies the headers property of the response
     * 
     * @param array $headers
     * @return static 
     */
    public function withHeaders(array $headers)
    {
        return new static($this->getBody(), $this->getStatusCode(), $headers);
    }

    /**
     * immutable interface adding header value to response headers
     * 
     * @param string $name 
     * @param mixed $value 
     * @return static 
     */
    public function withAddedHeader(string $name, $value)
    {
        $headers = $this->getHeaders();
        if (array_key_exists($name, $headers) && is_array($headers[$name])) {
            $headers[$name][] = $value;
        } else {
            $headers[$name] = !empty($headers[$name]) ? array_merge([$headers[$name]], [$value]) : $value;
        }
        return $this->withHeaders($headers);
    }
}
