<?php

namespace Gulp\Http\Client;

class RequestFactory
{
	/** @var string Class to instantiate for requests */
	protected $requestClass = '\\Gulp\\Http\\Client\\Request';

	/** @var string Class to instantiate for headers */
	protected $headerClass = '\\Gulp\\Http\\Client\\Header';

    public function create($method, $url, $headers = null, $body = null, array $options = [])
    {
        $method = strtoupper($method);
        $headers = new $this->headerClass(is_array($headers) ? $headers : [$headers]);

        if ($method == 'GET' || $method == 'HEAD' || $method == 'TRACE' || $method == 'OPTIONS') {
            // Handle non-entity-enclosing request methods
            $request = new $this->requestClass($method, $url, $headers);
            if ($body) {
                // The body is where the response body will be stored
                $type = gettype($body);
                if ($type == 'string' || $type == 'resource' || $type == 'object') {
                    $request->setResponseBody($body);
                }
            }
        } else {
            // Create an entity enclosing request by default
            $request = new $this->requestClass($method, $url, $headers);
#d($method, $url, $headers, $body, $options);

            if ($body) {
				$request->getHeader()->set('Content-Type', 'application/x-www-form-urlencoded');
				$request->addPostFields($body);
d($request);

                // Add POST fields and files to an entity enclosing request if an array is used
                if (is_array($body)) {
                    // Normalize PHP style cURL uploads with a leading '@' symbol
                    foreach ($body as $key => $value) {
                        if (is_string($value) && substr($value, 0, 1) == '@') {
                            $request->addPostFile($key, $value);
                            unset($body[$key]);
                        }
                    }
                    // Add the fields if they are still present and not all files
                    $request->addPostFields($body);
                } else {
                    // Add a raw entity body body to the request
                    $request->setBody($body, (string) $request->getHeader('Content-Type'));
                    if ((string) $request->getHeader('Transfer-Encoding') == 'chunked') {
                        $request->removeHeader('Content-Length');
                    }
                }
            }
        }

        if ($options) {
            $this->applyOptions($request, $options);
        }

        return $request;
    }
}
