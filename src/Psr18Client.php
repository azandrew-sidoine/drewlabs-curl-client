<?php

namespace Drewlabs\Curl;

use Drewlabs\Psr7\Exceptions\NetworkException;
use Drewlabs\Psr7\Exceptions\RequestException;
use Drewlabs\Psr7\Response;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;

/**
 * @method static Psr18Client new(string $base_url, $options = [])
 * 
 * @package Drewlabs\Curl
 */
class Psr18Client implements ClientInterface
{
    use HasClientOptions;
    /**
     * 
     * @var Client
     */
    private $client;

    /**
     * Makes sure an instance of Psr18Client is not created using `new Psr18Client()`
     */
    private function __construct()
    {
    }

    /**
     * Insure the request is send with content type application/json
     * 
     * @return static 
     * @throws InvalidArgumentException 
     */
    public function json()
    {
        return $this->setRequestHeader('Content-Type', 'application/json');
    }

    /**
     * Insure the request is send with content type multipart/form-data
     * 
     * @return static 
     * @throws InvalidArgumentException 
     */
    public function multipart()
    {
        return $this->setRequestHeader('Content-Type', 'multipart/form-data');
    }

    /**
     * use Digest auth
     * 
     * @param string $user 
     * @param string $password 
     * @return static 
     */
    public function digestAuth(string $user, string $password)
    {
        return $this->setRequestAuth($user, $password, 'digest');
    }

    /**
     * use Basic request authentication
     * 
     * @param string $user 
     * @param string $password 
     * @return $this 
     */
    public function basicAuth(string $user, string $password)
    {
        return $this->setRequestAuth($user, $password);
    }

    /**
     * Set a request header value
     * 
     * @param string $name 
     * @param string $value 
     * @return static 
     * @throws InvalidArgumentException 
     */
    public function setRequestHeader(string $name, $value)
    {
        $options = ($this->options ?? new ClientOptions());
        $request = $options->getRequest();
        $headers = $request->getHeaders();
        return $this->setOptions(
            $options->setRequest(
                $request->setHeaders(
                    $this->setHeader($headers, $name, $value)
                )
            )
        );
    }

    /**
     * Creates an instance of {@see Psr18Client}
     * 
     * @param string $base_url
     * @param ClientOptions|array $options 
     * @return Psr18Client 
     * @throws ReflectionException 
     */
    public static function new(string $base_url = null, $options = [])
    {
        /**
         * @var Psr18Client
         */
        $instance = (new \ReflectionClass(__CLASS__))->newInstanceWithoutConstructor();
        $instance->client = new Client(null, []);
        $instance->options = is_array($options) ? ClientOptions::create($options) : ($options ?? new ClientOptions());
        if ($base_url) {
            $instance->options->setBaseURL($base_url);
        }
        return $instance;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $request = $this->overrideRequest($request, $this->options);
        $options = $this->buildCurlRequestOptions($request);
        $this->client->setOptions($options);
        $this->client->send();
        if (($errorno = $this->client->getError()) !== 0) {
            // Throw Error if client return error code different from 0
            [$exceptionMessage, $errorCode] = [$this->client->getErrorMessage(), CurlError::toHTTPStatusCode($errorno)];
            if (
                in_array($errorno, [
                    CurlError::CURLE_COULDNT_CONNECT,
                    CurlError::CURLE_COULDNT_RESOLVE_HOST,
                    CurlError::CURLE_COULDNT_RESOLVE_PROXY,
                ])
            ) {
                throw new NetworkException($request, $exceptionMessage, $errorCode);
            }
            throw new RequestException($request, $exceptionMessage, $errorCode);
        }
        $statusCode = $this->client->getStatusCode();
        return new Response(
            ($statusCode >= 100) && ($statusCode <= 511) ? $statusCode : CurlError::toHTTPStatusCode($errorno),
            $this->client->getResponseHeaders(),
            // Because we do not use \CURLOPT_RETURNTRANSFERT option we must manually get the response body
            $this->options->getSink(),
            $this->client->getProtocolVersion(),
            $this->client->hasErrorr() ? $this->client->getErrorMessage() : null
        );
    }

    /**
     * Prepare the curl request
     * 
     * @param RequestInterface $request 
     * @return array 
     */
    public function buildCurlRequestOptions(RequestInterface $request)
    {
        $options = $this->appendCurlHeaders(
            $request,
            $this->appendClientOptions(
                $request,
                $this->options,
                $this->appendCurlBody(
                    $request,
                    $this->curlDefaults($request)
                )
            )
        );
        unset($options['__HEADERS__']);
        return $options;
    }

    /**
     * 
     * @param RequestInterface $request 
     * @return (string[][]|string|false|int)[] 
     */
    private function curlDefaults(RequestInterface $request)
    {
        $defaults = [
            '__HEADERS__'           => $request->getHeaders(),
            \CURLOPT_CUSTOMREQUEST  => $request->getMethod(),
            \CURLOPT_URL            => (string) $request->getUri()->withFragment(''),
            \CURLOPT_RETURNTRANSFER => false,
            \CURLOPT_HEADER         => false,
            \CURLOPT_CONNECTTIMEOUT => 150,
        ];
        if (\defined('CURLOPT_PROTOCOLS')) {
            $defaults[\CURLOPT_PROTOCOLS] = \CURLPROTO_HTTP | \CURLPROTO_HTTPS;
        }
        $version = $request->getProtocolVersion();
        if ($version == 1.1) {
            $defaults[\CURLOPT_HTTP_VERSION] = \CURL_HTTP_VERSION_1_1;
        } elseif ($version == 2.0) {
            $defaults[\CURLOPT_HTTP_VERSION] = \CURL_HTTP_VERSION_2_0;
        } else {
            $defaults[\CURLOPT_HTTP_VERSION] = \CURL_HTTP_VERSION_1_0;
        }
        return $defaults;
    }


    /**
     * 
     * @param RequestInterface $request 
     * @param array $options 
     * @return mixed 
     */
    private function appendCurlHeaders(RequestInterface $request, array $options)
    {
        // $options = $callback($request);
        foreach ($options['__HEADERS__'] as $name => $values) {
            foreach ($values as $value) {
                $value = (string) $value;
                if ($value === '') {
                    // cURL requires a special format for empty headers.
                    // See https://github.com/guzzle/guzzle/issues/1882 for more details.
                    $options[\CURLOPT_HTTPHEADER][] = "$name;";
                } else {
                    $options[\CURLOPT_HTTPHEADER][] = "$name: $value";
                }
            }
        }
        // Remove the Accept header if one was not set
        if (!$request->hasHeader('Accept')) {
            $options[\CURLOPT_HTTPHEADER][] = 'Accept:';
        }
        return $options;
    }

    /**
     * 
     * @param RequestInterface $request 
     * @param array $options
     * @return mixed 
     */
    private function appendBody(RequestInterface $request, array $options)
    {
        // $options = $callback($request);
        $size = $request->hasHeader('Content-Length') ? (int) $request->getHeaderLine('Content-Length') : null;
        if (($size !== null && $size < 1000000)) {
            $options[\CURLOPT_POSTFIELDS] = (string) $request->getBody();
            // Don't duplicate the Content-Length header
            $options = $this->removeHeaders($options, 'Content-Length', 'Transfer-Encoding');
        } else {
            $options[\CURLOPT_UPLOAD] =  true;
            if ($size !== null) {
                $options[\CURLOPT_INFILESIZE] =  $size;
                $options = $this->removeHeaders($options, 'Content-Length');
            }
            $body = $request->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $options[\CURLOPT_READFUNCTION] = static function ($curl, $fd, $length) use ($body) {
                return $body->read($length);
            };
        }

        // If the Expect header is not present, prevent curl from adding it
        if (!$request->hasHeader('Expect')) {
            $options[\CURLOPT_HTTPHEADER][] = 'Expect:';
        }

        // cURL sometimes adds a content-type by default. Prevent this.
        if (!$request->hasHeader('Content-Type')) {
            $options[\CURLOPT_HTTPHEADER][] = 'Content-Type:';
        }
        return $options;
    }

    /**
     * 
     * @param RequestInterface $request 
     * @param array $options
     * @return mixed 
     */
    private function appendCurlBody(RequestInterface $request, array $options)
    {
        // $options = $callback($request);
        [$body, $size] = [($body = $request->getBody()), $body->getSize()];
        if ($size === null || $size > 0) {
            return $this->appendBody($request, $options);
        }
        $method = $request->getMethod();
        if ($method === 'PUT' || $method === 'POST') {
            // See https://tools.ietf.org/html/rfc7230#section-3.3.2
            if (!$request->hasHeader('Content-Length')) {
                $options[\CURLOPT_HTTPHEADER][] = 'Content-Length: 0';
            }
        } elseif ($method === 'HEAD') {
            $options[\CURLOPT_NOBODY] = true;
            unset(
                $options[\CURLOPT_WRITEFUNCTION],
                $options[\CURLOPT_READFUNCTION],
                $options[\CURLOPT_FILE],
                $options[\CURLOPT_INFILE]
            );
        }
        return $options;
    }

    /**
     * Remove a header from the options array.
     *
     * @param array  $options Array of options to modify
     * @param string[] $name    Case-insensitive header to remove
     */
    private function removeHeaders(array $options, ...$names)
    {
        foreach ($names as $name) {
            foreach (\array_keys($options['__HEADERS__']) as $key) {
                if (!\strcasecmp($key, $name)) {
                    unset($options['__HEADERS__'][$key]);
                    return;
                }
            }
        }
        return $options;
    }

    private function setRequestAuth(string $user, string $pass, $type = 'basic')
    {
        $options = ($this->options ?? new ClientOptions());
        $request = $options->getRequest();
        return $this->setOptions(
            $options->setRequest($request->setAuth($user, $pass, $type))
        );
    }

    private function setHeader(array $headers, string $name, $value)
    {
        $normalized = strtolower($name);
        if (empty($headers)) {
            $headers[$normalized] = $value;
            return $headers;
        }
        foreach ($headers as $key => $_) {
            if (strtolower($key) === $normalized) {
                $headers[$normalized] = $headers;
            }
        }
        return $headers;
    }

    public function __clone()
    {
        $this->options = clone $this->options;
    }
}
