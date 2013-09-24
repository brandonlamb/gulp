PROTOTYPE
====

use Gulp\Request as Client;

$client = new Client('http://api.example.com/v1');

$request = $client
	->get('upload', ['User-Agent' => 'Gulp 1.0'])
	->header()->set('Accept-Type', 'application/json')
	;

$response = $request->send();

REQUIREMENTS
====

* Support all HTTP methods (GET, POST, PUT, DELETE, HEAD, OPTIONS, PATCH)
* Support file uploads
* Assume JSON responses
* Assume simple API workflow
