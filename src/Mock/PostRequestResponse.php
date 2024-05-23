<?php

namespace Drewlabs\Curl\Mock;

use donatj\MockWebServer\InitializingResponseInterface;
use donatj\MockWebServer\RequestInfo;

class PostRequestResponse implements InitializingResponseInterface
{
    /** @var string */
    private $body;

    /** @var array */
    private $headers;

    /** @var int */
    private $status;

    /**
     * Creates mock object instance
     * 
     * @param string $body 
     * @param array $headers 
     * @param int $status 
     * @return void 
     */
    public function __construct($body = '', array $headers = [], $status = 200)
    {
        $this->body    = $body;
        $this->headers = $headers;
        $this->status  = $status;
    }

    public function initialize(RequestInfo $request): void
    {
    }

    public function getRef(): string
    {
        $content = json_encode([
            $this->body,
            $this->status,
            $this->headers,
        ]);
        return md5('post-request.' . $content);
    }

    public function getBody(RequestInfo $request): string
    {
        return empty($this->body) ? json_encode($request->getPost()) : $this->body;
    }

    public function getHeaders(RequestInfo $request) : array
    {
		return $this->headers ?? [];
    }

    public function getStatus(RequestInfo $request): int
    {
		return $this->status ?? 200;
    }
}
