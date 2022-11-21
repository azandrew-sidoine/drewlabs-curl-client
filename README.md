# cURL Client

This project tends to implements an Object oriented PHP cURL client and provides a PSR18 implementation based on the cURL client object.

## Installation

Recommended way to install the library is by using PHP package manager `composer` running the command below:

> composer require drewlabs/curl-client

## Usage

The library comes with 2 cURL client, the 1st being an object oriented cURL class and PSR18 compatible client based on PHP cURL library.

### OOP cURL client

To create a new instance of the client, use the class constructor:

```php
use Drewlabs\Curl\Client;

// Creates an instance of the cURL client
$client = new Client();
```

This will create a new client instance and initialize a new curl session. Developpers can pass a base url to the client instance to define the host server url:

```php
use Drewlabs\Curl\Client;

// Creates an instance of the cURL client
$client = new Client('http://127.0.0.1:8000');
```

#### Curl Options

cURL options allow developpers to customize cURL request sent to the application server. Using `Client::setOption()` or `Client::setOptions()` for array like options, developpers can customize cURL requests being sent to the server.

```php
use Drewlabs\Curl\Client;

// Creates an instance of the cURL client
$client = new Client(/* Parameters */);

$client->setOption(\CURLOPT_RETURNTRANSFER, false);
$client->setOption(\CURLOPT_WRITE, function($curl, $write) {
    // Listener for response output from the curl session
});
```

In order to pass cURL options as array, we use the `setOptions()` array method:

```php

use Drewlabs\Curl\Client;

// Creates an instance of the cURL client
$client = new Client(/* Parameters */);

$client->setOptions([
    \CURLOPT_CUSTOMREQUEST  => 'POST',
    \CURLOPT_URL            => 'http://127.0.0.1:300/api/posts',
    \CURLOPT_RETURNTRANSFER => true,
    \CURLOPT_HEADER         => false,
    \CURLOPT_CONNECTTIMEOUT => 150,
]);
```

#### Sending cURL request

The client provides developpers with `Client::send()` method to send HTTP request to server:

```php
use Drewlabs\Curl\Client;

// Creates an instance of the cURL client
$client = new Client(/* Parameters */);

// ... Comnfigure cURL options
$client->send();

// To pass data to the request when sending POST, PUT, etc... method
$client->send([
    'title' => 'Hello World'
]);

// To specify the request url when sending the request
$client->send([], 'http://127.0.0.1:3000/api/posts');

// or simply a path
$client->send([], null, '/api/posts');

// To override or provide the request method
$client->send([], 'POST', '/api/posts');
```

- Helper methods

The client comes with some helper method for basic curl HTTP options like, `CURLOPT_URL`, `\CURLOPT_SSL_VERIFYPEER`, `\CURLOPT_HTTP_VERSION` etc... Below is a brief API definition for most common use cases:

> `Client::setRequestUri(string $url)` - Set the request URI
> `Client::setRequestMethod(string $method)` - Set the request method
> `Client::setProtocolVersion(string $version)` - Set the HTTP protocol version
> `Client::withAutoReferer()` -  Automatically set the auto referer header where it follows redirect
> `Client::followLocation()` -  Follows any location redirect unless \CURLOPT_MAXREDIR is sets.
> `Client::maxRedirects(int $n)` -  The number of redirect to be followed by the cURL client
> `Client::proxy($proxy, $port = null, $username = null, $password = null)` -  Defines an HTTP proxy through which the request is forwarded.
> `Client::setUserAgent($user_agent)` -  Defines the request user agent
> `Client::timeout(int $milliseconds)` -  Timeout the request for a given milliseconds
> `Client::verifyHost()` - Make sure the cURL client validate host domain and SSL certificate

**Note**
By default the cURL connection is closed the PHP runtime when the object is destructed. If developper which to close the curl session, or reset cURL parameters, the instance provides with `close()` and `release()` method for each specific case:

```php
use Drewlabs\Curl\Client;

// Creates an instance of the cURL client
$client = new Client(/* Parameters */);

// Sending the cURL request
$client->send();

// Closing the cURL session
$client->close();

// or reset cURL resources
$client->release();
```

## PSR18 Client

**Note**
Using the PSR18 client require the `drewlabs/psr7` library for the require classes. Use the command below to install the require dependencies.

> composer require drewlabs/psr7

The package comes with a PSR18 compatible Client using the PHP cURL library. To creates an instance of the client:

```php
use Drewlabs\Curl\Psr18Client;

// Creates an instance of the cURL client
$client = Psr18Client::new(/* Parameters */);

// Passing constructor parameters
$client = Psr18Client::new('http:://127.0.0.1:5000');

// Passing customize client options
$client = Psr18Client::new([
    /* Custom client options */
]);
```

### Client options

Client options, provide developpers with a way to override parameters passed to the `Client::sendRequest()` method. The package provide a PHP class for building client option as alternative to using PHP dictionary type (a.k.a PHP array).

- Creating the client options using a factory function

```php
use Drewlabs\Curl\ClientOptions;

// 
$clientOptions = ClientOptions::create([
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
```

- Using the fluent API

Alternative to using the factory function, we can use the fluent API for creating a client options. The fluent API attemps to reduce developper typo errors by providing methods to defining option values:

```php
use Drewlabs\Curl\ClientOptions;
use Drewlabs\Curl\RequestOptions;

$clientOptions = new ClientOptions;

$clientOptions->setBaseURL(/* base url*/)
    ->setRequest(RequestOptions::create([]))
    ->setConnectTimeout(150)
    ->setVerify(false)
    // Psr Stream to write response output to
    ->setSink()
    ->setForceResolveIp(true)
    ->setProxy($proxy_ip, [$proxy_port, $user, $password]) // port, user & password are optional depending on the proxy configuration
    ->setCert('/path/to/ssl/certificate');
    ->setSslKey('/path/to/ssl/key')
    ->setProgress(function($curl, ...$progress) {
        // Handle cURL progress event
    })
    ->setCookies([]); // List of request cookies

```

**Note**
API for request options & client option fluent API can be found in the API reference documentation.

### Sending a PSR18 request

Sending request is simply as using any PSR18 compatible library:

```php
use Drewlabs\Curl\Psr18Client;
use Drewlabs\Psr7\Request;

// Creates an instance of the cURL client
$client = Psr18Client::new([
    // Parameters to client options ...
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
]);

$response = $client->sendRequest(new Request()); // \Psr7\Http\ResponseInterface
```

To send a JSON request, developpers call the `Client::json()` method before sending the request to the server:

```php
use Drewlabs\Curl\Psr18Client;

// Creates an instance of the cURL client
$client = new Psr18Client(/* Parameters */);

// Sends a request with application/json as Content-Type
$client->json()->sendRequest(/* PSR7 compatible Request */);
```

Alternatively, to send a `multipart/form-data` request, developpers call the `Client::multipart()` method before sending the request to the server:

```php
use Drewlabs\Curl\Psr18Client;

// Creates an instance of the cURL client
$client = new Psr18Client(/* Parameters */);

// Sends a request with application/json as Content-Type
$client->json()->sendRequest(new Request());
```
