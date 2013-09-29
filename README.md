PROTOTYPE
====

## GET Request

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

// Get the parsed response
$response = $request->send();
```

**Note** Non-integer keys passed in the options array to a non-post client request will be extended into the query string as parameters.

## POST Request

```php
$client = new Gulp\Client('http://api.example.com/v1/');

// Set a default request option for User-Agent
$client->setDefaultOption('headers', ['User-Agent' => 'Gulp 1.0']);

// Create a new request
$request = $client->post(
    'upload',
    ['X-header-1' => 'My Header'],
    [
        'name' => 'Vacation',
        'date' => '2013-10-01 12:00:00',
        'file' => '@/tmp/file1.jpg'
    ]
);

// Get the parsed response
$response = $request->send();

// Get the response data as a json object (false is passed to json_decode)
$data = $response->json(false);
```

REQUIREMENTS
====

* Support all HTTP methods (GET, POST, PUT, DELETE, HEAD, OPTIONS, PATCH)
* Support file uploads
* Assume JSON responses
* Assume simple API workflow
