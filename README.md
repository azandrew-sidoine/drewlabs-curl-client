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

/ Passing request options to the cURL client
$client = new Client([
    'base_url' => 'http://127.0.0.1:5000',
    'headers' => [
        'Content-Type' => 'application/json',
    ],
    'cookies' => [
        'clientid' => '...',
        'clientsecret' => '...'
    ]
]);


// Create request client with a base URL
$client = new Client('http://127.0.0.1:5000');
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

// ... Use pre-configure cURL options
$client->send();

// Passing request options
$client->send([
    'headers' => [
        'Content-Type' => 'application/json'
    ],
    'body' => [
        'title' => 'Hello World'
    ]
]);

// To specify the request url when sending the request
$client->send('GET', 'http://127.0.0.1:3000/api/posts');

// or simply a path
$client->send(null, '/api/posts');

// To override or provide the request method
$client->send('POST', '/api/posts');
```

Sometimes you may have constructed the curl request and wish to simply execute the curl request. To do so the client mimic the curl `exec()` method by providing `Client::exec()` method for sending request to server.

```php
use Drewlabs\Curl\Client;

// Creates an instance of the cURL client
$client = new Client(/* Parameters */);

$client->setRequestMethod('POST');
$client->setRequestUri('http://127.0.0.1:5000/api/posts');
$this->setOption(CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: */*'
])
$client->setOption(\CURLOPT_POSTFIELDS, json_encode([/* JSON fields*/]));

// Execute the Curl request
$client->exec();
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
