<?php

namespace Drewlabs\Curl;

use ReflectionClass;
use Psr\Http\Message\StreamInterface;

class ClientOptions
{
    /**
     * 
     * @var bool
     */
    private $verify;

    /**
     * 
     * @var string
     */
    private $sink;

    /**
     * 
     * @var bool
     */
    private $force_resolve_ip;

    /**
     * 
     * @var array
     */
    private $proxy;

    /**
     * 
     * @var array<string>
     */
    private $cert;

    /**
     * 
     * @var array<string>
     */
    private $ssl_key;

    /**
     * 
     * @var \Closure|callable
     */
    private $progress;

    /**
     * 
     * @var string
     */
    private $base_url;

    /**
     * 
     * @var int
     */
    private $connect_timeout;

    /**
     * 
     * @var RequestOptions
     */
    private $request;

    /**
     * 
     * @var CookiesBag
     */
    private $cookies = [];

    /**
     * Creates an instance of the clien options class
     * 
     * @param string $base_url
     * @param bool|null $verify 
     * @param int|null $timeout 
     * @param Closure $progress 
     * @param string|null $tmpWritePath 
     * @return void 
     */
    public function __construct(
        string $base_url = null,
        bool $verify = null,
        int $timeout = null,
        \Closure $progress = null,
        string $tmpWritePath = null
    ) {
        $this->base_url = $base_url;
        $this->verify = $verify;
        $this->timeout = $timeout;
        $this->progress = $progress;
        $this->sink = $tmpWritePath;
    }


    /**
     * Create a new {@see \Drewlabs\Curl\ClientOptions} instance
     * 
     * @param array $properties 
     * @return static 
     * @throws ReflectionException 
     */
    public static function create(array $properties = [])
    {
        if (is_array($properties)) {
            $instance = (new ReflectionClass(__CLASS__))->newInstanceWithoutConstructor();
            foreach ($properties as $name => $value) {
                if (null === $value) {
                    continue;
                }
                // Tries to generate a camelcase method name from property name and prefix it with set
                if (method_exists($instance, $method = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name))))) {
                    \Closure::fromCallable([$instance, $method])->call($instance, $value);
                    continue;
                }
                if (property_exists($instance, $name)) {
                    $instance->{$name} = $value;
                    continue;
                }
            }
            return $instance;
        }

        return new static;
    }

    /**
     * Set the verify client option value
     * 
     * @param bool $value 
     * @return static 
     */
    public function setVerify(bool $value)
    {
        $this->verify = $value;
        return $this;
    }

    /**
     * Configuration value for whether the host ssl is verified
     * 
     * @return bool 
     */
    public function getVerify()
    {
        return $this->verify;
    }

    /**
     * The path where downloaded files are temporary written to
     * 
     * @param StreamInterface $value 
     * @return static 
     */
    public function setSink(StreamInterface $value)
    {
        $this->sink = $value;
        return $this;
    }

    /**
     * Get the path where downloaded files are written to
     * 
     * @return StreamInterface
     */
    public function getSink()
    {
        return $this->sink;
    }

    /**
     * Force resolve host ip configuration value
     * 
     * @param bool $value 
     * @return static 
     */
    public function setForceResolveIp(bool $value)
    {
        $this->force_resolve_ip = $value;
        return $this;
    }

    /**
     * Force resolve host ip configuration value
     * 
     * @return bool 
     */
    public function getForceResolveIp()
    {
        return $this->force_resolve_ip ?? false;
    }


    /**
     * Set the proxy configuration used by the client
     * 
     * @param string|array $proxy 
     * @param mixed $port 
     * @param mixed $username 
     * @param mixed $password 
     * @return static 
     */
    public function setProxy($proxy, $port = null, $username = null, $password = null)
    {
        $this->proxy = is_array($proxy) ? $proxy : array_filter(func_get_args());
        return $this;
    }

    /**
     * Get the proxy configuration used by the client
     * 
     * @return array 
     */
    public function getProxy()
    {
        return $this->proxy ?? [];
    }

    /**
     * SSL Certificate password option.
     * 
     * ```php
     * $options = new ClientOptions();
     * 
     * $options->setCert('path/to/cert', $password);
     * ``` 
     * 
     * @param string|array $value 
     * @param string|null $value 
     * @return static 
     */
    public function setCert($path, string $password = null)
    {
        $this->cert = is_array($path) ? $path : array_filter([$path, $password]);
        return $this;
    }

    /**
     * Get the SSL Certificate password option.
     * 
     * @return array 
     */
    public function getCert()
    {
        return $this->cert;
    }

    /**
     * Set the SSL key option.
     * 
     * ```php
     * $options = new ClientOptions();
     * 
     * $options->setSslKey('path/to/cert', $password);
     * ``` 
     * 
     * @param string|array $value 
     * @param string|null $value 
     * @return static 
     */
    public function setSslKey($path, string $password = null)
    {
        $this->ssl_key =  is_array($path) ? $path : array_filter([$path, $password]);
        return $this;
    }

    /**
     * Get SSL key option.
     * 
     * @return array 
     */
    public function getSslKey()
    {
        return $this->ssl_key ?? false;
    }

    /**
     * Set the lister of the progress event on the client
     * 
     * @param Closure|null $value 
     * 
     * @return static
     */
    public function setProgress(callable $value)
    {
        $this->progress = $value;
        return $this;
    }

    /**
     * Set the lister of the progress event on the client
     * 
     * @return callable 
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * The Request client base url
     * 
     * @param string|null $value 
     * 
     * @return static 
     */
    public function setBaseURL(string $value)
    {
        $this->base_url = $value;
        return $this;
    }

    /**
     * The Request client base url
     * 
     * @return string 
     */
    public function getBaseURL()
    {
        return $this->base_url;
    }

    /**
     * Client Connection timeout
     * 
     * @param int $value
     * 
     * @return int 
     */
    public function setConnectTimeout(int $value)
    {
        $this->connect_timeout = $value;
        return $this;
    }

    /**
     * Client Connection timeout
     * 
     * @return int 
     */
    public function connectTimeout()
    {
        return $this->connect_timeout;
    }

    /**
     * Set the request options parameters
     * 
     * @param array|RequestOptions $options 
     * @return static 
     */
    public function setRequest($request)
    {
        $this->request = is_array($request) ? RequestOptions::create($request) : $request;
        return $this;
    }

    /**
     * Return the list of request options provided to the request client
     * 
     * @return RequestOptions 
     */
    public function getRequest()
    {
        return is_array($this->request) ? RequestOptions::create($this->request) : $this->request ?? new RequestOptions();
    }

    /**
     * Set the request cookies
     * 
     * @param array|CookiesBag $value 
     * @return $this 
     */
    public function setCookies($value)
    {
        $this->cookies = is_array($value) ? new CookiesBag($value) : $value ?? new CookiesBag();
        return $this;
    }

    /**
     * Returns the list of cookies of the current request
     * 
     * @return CookiesBag 
     */
    public function getCookies()
    {
        return $this->cookies;
    }
}
