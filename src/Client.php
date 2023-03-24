<?php

namespace Drewlabs\Curl;

use CurlHandle;
use RuntimeException;
use ErrorException;
use InvalidArgumentException;

class Client
{

    /**
     * Current package version
     * 
     * @var string
     */
    const VERSION = '0.1.0';

    /**
     * 
     * @var \CurlHandle
     */
    private $curl;

    /**
     * 
     * @var string
     */
    private $response;

    /**
     * 
     * @var string
     */
    private $id;

    /**
     * 
     * @var string
     */
    private $protocolVersion = '1.1';

    /**
     * 
     * @var string
     */
    private $curlErrorMessage;

    /**
     * 
     * @var int
     */
    private $curlError;

    /**
     * 
     * @var CurlHeadersCallback
     */
    private $curlHeaderCallback;

    /**
     * List of event listeners for the current client
     * 
     * @var array
     */
    private $listeners = [];

    /**
     * Request options property
     * 
     * @var array
     */
    private $options = [];

    /**
     * 
     * @var int
     */
    private $statusCode;

    /**
     * 
     * @var string
     */
    private $rawResponseHeaders;

    /**
     * 
     * @var string
     */
    private $base_url;

    /**
     * 
     * @var string
     */
    private $method;

    /**
     * Creates an instance of PHP cURL controller
     * 
     * @param string|array|null $baseUrlOrOptions 
     * @param array $options 
     * 
     * @throws ErrorException 
     * @throws RuntimeException 
     */
    public function __construct($baseUrlOrOptions = null, array $options = [])
    {
        // Makes the constructor method polymorphique
        $options = is_array($baseUrlOrOptions) ? $baseUrlOrOptions : $options;
        $baseUrlOrOptions = is_array($baseUrlOrOptions) ? null : $baseUrlOrOptions;
        $this->initialize($baseUrlOrOptions, $options);
    }

    /**
     * Executes instance initialization logic. It initializes required
     * properties and configure listeners
     * 
     * @return void 
     * @throws RuntimeException 
     */
    public function init()
    {
        $this->initialize();
    }

    /**
     * Execute or send the curl request
     * 
     * @param string|array|null $method 
     * @param string|array|null $path 
     * @param array<string,string|string[]> $options
     * 
     * @return void 
     * @throws RuntimeException 
     */
    public function send($method = null, $path = null, array $options = [])
    {
        if (func_num_args() > 0) {
            $options1 = is_array($method) ? $method : (is_string($method) ? ['method' => $method] : []);
            $options2 = is_array($path) ? $path : (is_string($path) ? ['url' => $path] : []);
            $options = $this->prepareRequestOptions(array_merge($options ?? [], $options1 ?? [], $options2 ?? []));
            // Then we set the request options
            $this->setRequestOptions($options);
        }
        // Executes the curl request
        $this->exec();
    }

    /**
     * Execute the constructed curl request
     * 
     * @return void 
     */
    public function exec()
    {
        if (!empty($progressListerners = ($this->listeners['progress'] ?? []))) {
            $this->setOption(CURLOPT_NOPROGRESS, false);
            $this->setOption(CURLOPT_PROGRESSFUNCTION, function (...$args) use ($progressListerners) {
                foreach ($progressListerners as $callback) {
                    if (is_callable($callback)) {
                        $callback(...$args);
                    }
                }
            });
        }
        // Executes the curl request
        $rawResponse = curl_exec($this->curl);
        // Get the curl session error number, error messages, and response code
        $this->curlError = curl_errno($this->curl);
        $curlErrorMessage = curl_error($this->curl);
        if (empty($curlErrorMessage) && (0 !== $this->curlError)) {
            $curlErrorMessage = curl_strerror($this->curlError);
        }
        $this->curlErrorMessage = $curlErrorMessage;
        $this->statusCode  = $this->getInfo(CURLINFO_RESPONSE_CODE);
        $this->rawResponseHeaders = $this->curlHeaderCallback->getHeaders();
        $this->response = $rawResponse;
    }

    /**
     * Returns the instance id
     * 
     * @return string 
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Set the request method to use for the given request
     * 
     * @param string $method 
     * @return static 
     */
    public function setRequestMethod($method = 'GET')
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Set the request base url property
     * 
     * @param string $url 
     * 
     * @return self 
     */
    public function setBaseUri(string $url)
    {
        $this->base_url = $url;
        return $this;
    }

    /**
     * Set the request uri to use for the given request
     * 
     * @param string|Stringable $method 
     * @return static 
     */
    public function setRequestUri($url)
    {
        $url = (string)$url;
        $this->assertRequesUrl($url);
        $this->setOption(CURLOPT_URL, (string)$url);
        return $this;
    }

    /**
     * Disables SSL verification
     * 
     * @return void 
     */
    public function disableSSLVerification()
    {
        $this->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->setOption(CURLOPT_SSL_VERIFYHOST, 0);
        return $this;
    }

    /**
     * Verify the host/server domain
     * 
     * @return static 
     */
    public function verifyHost()
    {
        $this->setOption(CURLOPT_SSL_VERIFYPEER, true);
        $this->setOption(CURLOPT_SSL_VERIFYHOST, 2);
        return $this;
    }

    /**
     * Returns the Request response object
     * 
     * @return string|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * 
     * @return bool 
     */
    public function hasErrorr()
    {
        return (in_array((int) floor($this->statusCode / 100), [4, 5], true)) && (0 !== $this->curlError);
    }

    /**
     * Returns the curl error message or empty string if no error message present
     * 
     * @return string 
     */
    public function getErrorMessage()
    {
        return $this->curlErrorMessage ?? '';
    }
    /**
     * Returns the curl error if any
     * 
     * @return int|null
     */
    public function getError()
    {
        return $this->curlError;
    }

    /**
     * Returns the response status code
     * 
     * @return int|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Returns the raw response headers
     * 
     * @return string|null 
     */
    public function getResponseHeaders()
    {
        return $this->rawResponseHeaders;
    }
    /**
     * 
     * @param string $version 
     * 
     * @throws InvalidArgumentException 
     */
    public function setProtocolVersion($version = '1.0')
    {
        if (!is_numeric($version)) {
            throw new InvalidArgumentException('HTTP protocol versin must be a valid protocol version');
        }
        switch ((string)$version) {
            case '1.1':
                $this->protocolVersion = \CURL_HTTP_VERSION_1_1;
                break;
            case '2.0':
                $this->protocolVersion = \CURL_HTTP_VERSION_2_0;
                break;
            default:
                $this->protocolVersion = \CURL_HTTP_VERSION_1_0;
                break;
        }
        $this->setOption(CURLOPT_HTTP_VERSION, $this->protocolVersion);
    }

    /**
     * Returns the Protocol version used by the curl client
     * 
     * @return string 
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * Set auto referrer
     *
     */
    public function withAutoReferer()
    {
        $this->setOption(CURLOPT_AUTOREFERER, true);
    }

    /**
     * Set the follow location option
     * 
     * @return void 
     */
    public function followLocation()
    {
        $this->setOption(CURLOPT_FOLLOWLOCATION, true);
    }

    /**
     * 
     * @return void 
     */
    public function forbidReuse()
    {
        $this->setOption(CURLOPT_FORBID_REUSE, true);
    }

    /**
     * Set maximum redirects
     * 
     * @param int $max 
     * @return void 
     */
    public function maxRedirects(int $max)
    {
        $this->setOption(CURLOPT_MAXREDIRS, $max);
    }

    /**
     *
     * HTTP proxy to tunnel requests through.
     *
     * @access public
     * @param  $proxy - The HTTP proxy to tunnel requests through. May include port number.
     * @param  $port - The port number of the proxy to connect to. This port number can also be set in $proxy.
     * @param  $username - The username to use for the connection to the proxy.
     * @param  $password - The password to use for the connection to the proxy.
     */
    public function proxy($proxy, $port = null, $username = null, $password = null)
    {
        $this->setOption(CURLOPT_PROXY, $proxy);
        if ($port !== null) {
            $this->setOption(CURLOPT_PROXYPORT, $port);
        }
        if ($username !== null && $password !== null) {
            $this->setOption(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
        }
    }
    /**
     *
     * Configure HTTP proxy to tunnel requests through.
     *
     * @access public
     * @param  $proxy - The HTTP proxy to tunnel requests through. May include port number.
     * @param  $port - The port number of the proxy to connect to. This port number can also be set in $proxy.
     * @param  $username - The username to use for the connection to the proxy.
     * @param  $password - The password to use for the connection to the proxy.
     */
    public function through($proxy, $port = null, $username = null, $password = null)
    {
        return $this->proxy($proxy, $port, $username, $password);
    }


    /**
     * Add an event listener for event emitted by the cURL handle
     * 
     * @param string $type 
     * @param mixed $callback 
     * @return void 
     */
    public function addEventListener(string $type, $callback)
    {
        $type = strtolower($type);
        if (!in_array($type, array_keys($this->listeners))) {
            return;
        }
        $this->listeners[$type] = array_merge($this->listeners[$type] ?? [], [$callback]);
    }

    /**
     * Set User Agent
     *
     * @param  $user_agent
     */
    public function setUserAgent($user_agent)
    {
        $this->setOption(CURLOPT_USERAGENT, $user_agent);
    }

    /**
     *
     * The name of the outgoing network interface to use.
     * This can be an interface name, an IP address or a host name.
     *
     * @access public
     * @param  $interface
     */
    public function usingInterface($interface)
    {
        $this->setOption(CURLOPT_INTERFACE, $interface);
        return $this;
    }

    /**
     * The maximum number of milliseconds to allow client to executes.
     * 
     * @param int $milliseconds 
     * 
     * @return static 
     */
    public function timeout(int $milliseconds)
    {
        $this->setOption(CURLOPT_TIMEOUT_MS, $milliseconds);
        return $this;
    }


    /**
     * Result the client for the current session 
     * 
     * @return void 
     */
    public function reset()
    {
        \curl_reset($this->curl);
    }

    /**
     * Close the cURL session
     * 
     * @return void 
     */
    public function close()
    {
        // We close the curl connection when we dispose the current instance
        \curl_close($this->curl);
    }

    /**
     * Get cURL info for the current session
     * 
     * @param int|null $option 
     * @return mixed 
     */
    public function getInfo(int $option = null)
    {
        return curl_getinfo($this->curl, $option);
    }

    /**
     * Set the curl option for the current session
     * 
     * @param int $key 
     * @param mixed $value 
     * @return void 
     */
    public function setOption(int $key, $value)
    {
        curl_setopt($this->curl, $key, $value);
    }

    /**
     * Set list of curl options on the current session
     * 
     * @param array $options 
     * @return void 
     */
    public function setOptions(array $options)
    {
        curl_setopt_array($this->curl, $options);
    }

    /**
     * Returns the client options
     * 
     * @return array 
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Provides the developper to build the request body based on user provided type
     * 
     * @param mixed $data 
     * @param string $contentType 
     * @return string|false|array 
     * @throws RuntimeException 
     */
    public function buildPostData($data, string $contentType = 'application/json')
    {
        $postData = new PostData($data);
        $builder = new PostDataBuilder($postData);
        if (
            (isset($contentType)) &&
            RegExp::matchJson($contentType) &&
            $postData->isJSONSerializable()
        ) {
            $builder = $builder->asJSON();
        } else if (!isset($contentType) || !preg_match('/^multipart\/form-data/', $contentType)) {
            $builder = $builder->asURLEncoded();
        }
        return $builder->build();
    }

    /**
     * Prepare the request options to use when executing request
     * 
     * @internal Do not call the current API as it internal implementation might change
     * 
     * @param array $options    List of options to override default options if exists or appended to the default options if not exists
     * @return array 
     * @throws RuntimeException 
     * @throws InvalidArgumentException 
     */
    public function prepareRequestOptions(array $options = [])
    {
        if (!empty($options)) {
            // We make sure curl options are not overwritten by the request options passed to the send
            // method
            $options = array_merge(($this->options ?? []), ($options ?? []), ['curl' => $this->options['curl'] ?? []]);
        }
        //#region - Make eht send() method polymophic by supporting diffent types for first and last parameters
        // Construct the request url by reading the $options as well
        if ((null === $this->base_url) && empty(isset($options['url']))) {
            throw new RuntimeException('Client::send() require a request url, none provided. Either call $client->setRequestUri() before calling $client->send(...) or call $client->send($params, $methodOrNull, $request_url)');
        }
        $path = $options['url'] ?? '';
        // Set the request URL
        $absolute = !empty($path) && ($compoents = \parse_url($path)) ? isset($compoents['host']) && isset($compoents['scheme']) : false;

        if (!$absolute && (null === $this->base_url)) {
            throw new InvalidArgumentException('Client base URL is require if $path parameter is not an absolute url.');
        }

        $this->setRequestUri($absolute ? str_replace("&amp;", "&", urldecode(urlencode(trim($path)))) : str_replace("&amp;", "&", urldecode(urlencode(trim(rtrim($this->base_url, '/') . (empty($path) ? '' : ('/' . ltrim($path, '/'))))))));

        // Returns the prepared options 
        return $options;
    }


    /**
     * Relese and reset curl session
     * 
     * @return void 
     */
    public function release()
    {
        $this->curlHeaderCallback = null;
        $this->curlError = null;
        $this->curlErrorMessage = null;
        $this->rawResponseHeaders = null;
        $this->statusCode = null;
        $this->response = null;
        $this->protocolVersion = '1.1';
        $this->initializeListeners();
        $this->setOption(\CURLOPT_HEADERFUNCTION, null);
        $this->setOption(\CURLOPT_READFUNCTION, null);
        $this->setOption(\CURLOPT_WRITEFUNCTION, null);
        $this->setOption(\CURLOPT_PROGRESSFUNCTION, null);
        $this->reset();
    }

    public function __destruct()
    {
        $this->release();
        $this->initializeListeners();
        $this->close();
    }

    /**
     * Initialize the cURL client
     * 
     * @param string $base_url 
     * @param array $options 
     * @return void 
     * @throws RuntimeException 
     */
    private function initialize($base_url = null, array $options = [])
    {
        $this->id = $this->id ?? uniqid('', true);
        $this->curl = $this->curl ?? $this->createCurlInstance();
        $this->curlHeaderCallback = new CurlHeadersCallback;
        $this->initializeListeners();
        // Initialization function is invoke to initialize the 
        $this->options = empty($this->options) ? $options ?? [] : $this->options ?? [];
        //
        if (isset($this->options['curl']) && is_array($curlOptions = $this->options['curl'])) {
            foreach ($curlOptions as $key => $value) {
                $this->setOption($key, $value);
            }
            if (!array_key_exists(CURLOPT_USERAGENT, $curlOptions)) {
                $this->useDefaultUserAgent();
            }
            if (!array_key_exists(CURLINFO_HEADER_OUT, $curlOptions)) {
                $this->setOption(CURLINFO_HEADER_OUT, true);
            }
        }
        // Set the function to handle the returned headers event
        $this->setOption(CURLOPT_HEADERFUNCTION, $this->curlHeaderCallback);
        // By default request result are exec result is returned to the client in a raw string
        $this->setOption(\CURLOPT_RETURNTRANSFER, true);
        $this->base_url = $this->base_url ?? ($base_url !== null ? $base_url : $this->options['base_url'] ?? null);
    }

    /**
     * Creates a new curl handle
     * 
     * @return CurlHandle|false 
     * @throws ErrorException 
     * @throws RuntimeException 
     */
    private function createCurlInstance()
    {
        if (!extension_loaded('curl')) {
            throw new \ErrorException('cURL library is not loaded, but is required by the library');
        }
        if (false === ($curl = curl_init())) {
            throw new RuntimeException('Failed to initialize a new curl session, Please ensure that you have curl extension installed and functionning properly');
        }
        return $curl;
    }

    /**
     * Initialize the listerners array
     * 
     * @return void 
     */
    private function initializeListeners()
    {
        foreach (($this->listeners ?? []) as $listeners) {
            $listeners = is_array($listeners) ? $listeners : [];
            foreach ($listeners as $listener) {
                if (null === $listener) {
                    continue;
                }
                unset($listener);
            }
        }
        $this->listeners = ['progress' => []];
    }

    /**
     * Resolve the user agent for the current client
     * 
     * @return string 
     */
    private function useDefaultUserAgent()
    {
        $agent = 'drewlabs/' . self::VERSION;
        $curl_version = curl_version();
        $agent .= ' curl/' . $curl_version['version'];
        return $agent;
    }

    private function assertRequesUrl($url)
    {
        $result = @parse_url($url);
        if (false === $result || (null === $result)) {
            throw new InvalidArgumentException('Request url does not match PHP url standard');
        }
    }

    /**
     * Set the request options
     * 
     * @param array $options 
     * @return void 
     * @throws RuntimeException 
     */
    private function setRequestOptions(array $options)
    {
        $this->setOption(CURLOPT_CUSTOMREQUEST, $options['method'] ?? 'GET');

        // Set the request headers if any
        $headers = $options['headers'] ?? [];
        if (!empty($headers)) {
            $this->setRequestHeaders($headers);
        }

        // Set the request cookies if any
        if (!empty($cookies = ($options['cookies'] ?? []))) {
            $this->setRequestCookies($cookies);
        }

        // Set the request body if any. The request body is parsed based on the parameters provided in the request
        //  Content-Type header
        if (!empty($body = ($options['body'] ?? []))) {
            $contentType = !empty($headers) ? $this->getHeader($headers, 'Content-Type') : 'application/x-www-form-urlencoded';
            $contentType = is_array($contentType) ? implode(',', $contentType) : $contentType ?? 'application/x-www-form-urlencoded';
            $postFields  = $this->buildPostData($body, $contentType);
            $this->setOption(CURLOPT_POSTFIELDS, $postFields);
        }
    }

    /**
     * 
     * @param array $cookies 
     * @return void 
     */
    private function setRequestCookies(array $cookies)
    {
        if (count($cookies)) {
            $this->setOption(CURLOPT_COOKIE, implode('; ', array_map(function ($key, $value) {
                return $key . '=' . $value;
            }, array_keys($cookies), array_values($cookies))));
        }
    }

    /**
     * Set the curl session headers
     * 
     * @param array $requestHeaders 
     * @return void 
     */
    private function setRequestHeaders(array $requestHeaders)
    {
        $headers = [];
        foreach ($requestHeaders as $key => $value) {
            $headers[] = $key . ': ' . (is_array($value) ? implode(', ', $value) : ($value ?? ''));
        }
        $this->setOption(CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Get request header caseless
     * 
     * @param array $headers 
     * @param string $name 
     * @return array<string>|null 
     */
    private function getHeader(array $headers, string $name)
    {
        if (empty($headers)) {
            return null;
        }
        $normalized = strtolower($name);
        foreach ($headers as $key => $header) {
            if (strtolower($key) === $normalized) {
                return is_array($header) ? $header : [$header];
            }
        }
        return null;
    }
}
