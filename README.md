PROTOTYPE
====

```php
// Create new client
$client = new Gulp\Client('http://api.example.com/v1/');

// Set a default request option for User-Agent
$client->setDefaultOption('headers', ['User-Agent' => 'Gulp 1.0']);

// Create a new request
$request = $client->get(
    'upload',
    ['X-header' => 'My Header', 'Accept-Type' => 'application/json'],
    ['name' => 'John Doe', CURLOPT_TIMEOUT => 60]
);

$response = $request->send();
```

Non-integer keys passed in the options array to a non-post client request will be extended into the query string as parameters.

REQUIREMENTS
====

* Support all HTTP methods (GET, POST, PUT, DELETE, HEAD, OPTIONS, PATCH)
* Support file uploads
* Assume JSON responses
* Assume simple API workflow
