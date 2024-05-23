<?php

namespace Drewlabs\Curl\Utils;

use Drewlabs\Curl\Converters\JSONDecoder;
use JsonException;

class Response
{
    use ResponseTrait;

    /** @var string */
    private $body = null;

    /**
     * Creates response instance
     * 
     * @param string $body 
     * @param int $status 
     * @param array $headers
     * 
     * @return void 
     */
    public function __construct(string $body = '', int $status = 200, array $headers = [])
    {
        $this->body = $body;
        $this->headers = $headers;
        $this->status = $status;
    }

    /**
     * returns the raw response string body
     * 
     * @return string 
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * clone the current instance to a new response instance
     * 
     * @return static 
     */
    public function clone()
    {
        return new static($this->getBody(), $this->getStatusCode(), $this->getHeaders());
    }

    /**
     * immutable interface that modifies the response body instance
     * 
     * @param string $body
     * 
     * @return static 
     */
    public function withBody(string $body)
    {
        return new static($body, $this->getStatusCode(), $this->getHeaders());
    }

    /**
     * Creates a json decoded response object from the current response
     * 
     * @return JsonResponse 
     * @throws JsonException 
     */
    public function json()
    {
        $result = null;
        if (RegExp::matchJson($this->getHeader('content-type', 'application/json'))) {
            $result = (new JSONDecoder(true))->decode($this->body);
        }
        // If the Content-Type header is not present in the response headers, we apply the try catch clause
        // in order not thrown any exection no error is thrown when decoding.
        if (null === $result) {
            try {
                $result = (new JSONDecoder(true))->decode($this->body) ?? [];
            } catch (\Throwable $_) {
                $result = [];
            }
        }

        return  new JsonResponse((array) ($result ?? []), $this->getStatusCode(), $this->getHeaders());
    }
}