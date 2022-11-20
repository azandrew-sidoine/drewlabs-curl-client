<?php

namespace Drewlabs\Curl;

use RuntimeException;
use ErrorException;
use InvalidArgumentException;

class Client
{
    /**
     * 
     * @var string
     */
    private const JSON_PATTERN = '/^(?:application|text)\/(?:[a-z]+(?:[\.-][0-9a-z]+){0,}[\+\.]|x-)?json(?:-[a-z]+)?/i';

    /**
     * Current package version
     * 
     * @var string
     */
    private $version = '0.1.0';

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
    private $curlErrorMessage = '';

    /**
     * 
     * @var int
     */
    private $curlError = '';

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
    private $listeners;

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
     * Creates an instance of PHP cURL controller
     * 
     * @param string|null $base_url 
     * @param array $options 
     * 
     * @throws ErrorException 
     * @throws RuntimeException 
     */
    public function __construct($base_url = null, array $options = [])
    {
        $this->initialize($base_url, $options);
    }

    /**
     * Execute the current request
     * 
     * @param array|object|string $data 
     * 
     * @return void 
     */
    public function send($data = [])
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
        if (!empty($data)) {
            $this->setOption(CURLOPT_POSTFIELDS, $this->buildPostData($data));
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
     * Set the request method to use for the given request
     * 
     * @param string $method 
     * @return static 
     */
    public function setRequestMethod($method = 'GET')
    {
        $this->setOption(CURLOPT_CUSTOMREQUEST, $method);
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
     * @return string 
     */
    public function getResponse()
    {
        if (null === $this->response) {
            throw new RuntimeException('cURL response is not available. Make sure you invoke the execute method before calling getResponse() method');
        }
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
     * @return int 
     */
    public function getError()
    {
        return $this->curlError;
    }

    /**
     * Returns the response status code
     * 
     * @return int 
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Returns the raw response headers
     * 
     * @return string 
     */
    public function getResponseHeaders()
    {
        return $this->rawResponseHeaders;
    }
    /**
     * 
     * @param mixed $version 
     * @return void 
     * @throws InvalidArgumentException 
     */
    public function setProtocolVersion($version)
    {
        if (!is_numeric($version)) {
            throw new InvalidArgumentException('HTTP protocol versin must be a valid protocol version');
        }
        $this->protocolVersion = $version;
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
     * Relese and reset curl session
     * 
     * @return void 
     */
    public function release()
    {
        $this->curlHeaderCallback = null;
        $this->curlError = null;
        $this->curlErrorMessage = null;
        $this->initializeListeners();
        $this->setOption(\CURLOPT_HEADERFUNCTION, null);
        $this->setOption(\CURLOPT_READFUNCTION, null);
        $this->setOption(\CURLOPT_WRITEFUNCTION, null);
        $this->setOption(\CURLOPT_PROGRESSFUNCTION, null);
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
        if ($this->curl) {
            \curl_close($this->curl);
        }
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
     * Initialize the cURL client
     * 
     * @param string $base_url 
     * @param array $options 
     * @return void 
     * @throws RuntimeException 
     */
    private function initialize($base_url = null, array $options = [])
    {
        if (!extension_loaded('curl')) {
            throw new \ErrorException('cURL library is not loaded, but is required by the library');
        }
        $this->id = uniqid('', true);
        if (false === $curl = curl_init()) {
            throw new RuntimeException('Failed to initialize a new curl session, Please ensure that you have curl extension installed and functionning properly');
        }
        $this->curl = $curl;
        $this->curlHeaderCallback = new CurlHeadersCallback;
        $this->initializeListeners();
        $this->options = $options ?? [];
        //
        if (isset($this->options)) {
            foreach ($this->options as $key => $value) {
                $this->setOption($key, $value);
            }
        }
        // By default we want to manually handle return result of the curl request
        $this->setOption(CURLOPT_RETURNTRANSFER, true);
        // Set the function to handle the returned headers event
        $this->setOption(CURLOPT_HEADERFUNCTION, $this->curlHeaderCallback);
        if (!array_key_exists(CURLOPT_USERAGENT, $this->options)) {
            $this->useDefaultUserAgent();
        }
        if (!array_key_exists(CURLINFO_HEADER_OUT, $this->options)) {
            $this->setOption(CURLINFO_HEADER_OUT, true);
        }
        if ($base_url !== null) {
            $this->setOption(CURLOPT_URL, $base_url);
        }
    }

    /**
     * Initialize the listerners array
     * 
     * @return void 
     */
    private function initializeListeners()
    {
        $this->listeners = ['progress' => []];
    }
    
    /**
     * Build the request post data
     * 
     * @param mixed $data 
     * @param string $contentType 
     * @return string|false|array 
     * @throws RuntimeException 
     */
    private function buildPostData($data, string $contentType = 'application/json')
    {
        $postData = new PostData($data);
        $builder = new PostDataBuilder($postData);
        if (
            (isset($contentType)) &&
            preg_match(self::JSON_PATTERN, $contentType) &&
            $postData->isJSONSerializable()
        ) {
            $builder = $builder->asJSON();
        } else if (!isset($contentType) || !preg_match('/^multipart\/form-data/', $contentType)) {
            $builder = $builder->asURLEncoded();
        }
        return $builder->build();
    }

    /**
     * Resolve the user agent for the current client
     * 
     * @return string 
     */
    private function useDefaultUserAgent()
    {
        $agent = 'TxnClient/' . $this->version;
        $curl_version = curl_version();
        $agent .= ' curl/' . $curl_version['version'];
        return $agent;
    }

    public function __destruct()
    {
        $this->release();
        $this->close();
    }
}
