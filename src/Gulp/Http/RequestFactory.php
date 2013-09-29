<?php

namespace Gulp\Http;

class RequestFactory
{
	/** @var string Class to instantiate for requests */
	protected $requestClass = '\\Gulp\\Http\\Request';

    /** @var string Class to instantiate for responses */
    protected $responseClass = '\\Gulp\\Http\\Response';

	/** @var string Class to instantiate for headers */
	protected $headerClass = '\\Gulp\\Http\\Header';

    /** @var string Class to instantiate for curl wrapper */
    protected $curlClass = '\\Gulp\\Curl';

    /**
     * Factory method to create request objects
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param array|string $body
     * @param array $options
     */
    public function create($method, $url, array $headers = null, $body = null, array $options = [])
    {
        $method = strtoupper($method);
        $handle = new $this->curlClass();
        $headers = new $this->headerClass(is_array($headers) ? $headers : [$headers]);
        $response = new $this->responseClass(new $this->headerClass());
        $request = new $this->requestClass($method, $url, $handle, $headers, $response);

        if ($method == 'GET' || $method == 'HEAD' || $method == 'TRACE' || $method == 'OPTIONS') {
            if ($body) {
                // The body is where the response body will be stored
                $type = gettype($body);
                if ($type == 'string' || $type == 'resource' || $type == 'object') {
                    $request->setResponseBody($body);
                }
            }
        } else {
            if ($body) {
				$request->header()->set('Content-Type', 'application/x-www-form-urlencoded');

                // Add POST fields and files to an entity enclosing request if an array is used
                if (is_array($body)) {
                    // Normalize PHP style cURL uploads with a leading '@' symbol
                    foreach ($body as $key => $value) {
                        if (is_string($value) && is_string($value) && $value{0} == '@') {
                            $request->addPostFile($key, $value);
                            unset($body[$key]);
                        }
                    }
                    // Add the fields if they are still present and not all files
                    $request->addPostFields($body);
                } else {
                    // Add a raw entity body body to the request
                    $request->setBody($body, (string) $request->header()->get('Content-Type'));
                    if ((string) $request->header()->get('Transfer-Encoding') == 'chunked') {
                        $request->removeHeader('Content-Length');
                    }
                }
            }
        }

        $options && $this->applyOptions($request, $options);

        return $request;
    }

    public function applyOptions(Request $request, array $options = [])
    {
        $method = $request->method();
        if ($method === 'GET' || $method === 'HEAD' || $method === 'TRACE' || $method === 'OPTIONS') {
            $params = [];

            foreach ($options as $key => $value) {
                $type = gettype($key);
                if ($type !== 'integer') {
                    $params[$key] = $value;
                    unset($options[$key]);
                }
            }

            // If there were parameters passed in options, extend the query string and set the request's url
            count($params) && $request->setOption(CURLOPT_URL, $request->client()->getUri()->extendQuery($params)->build());
        }

        $options && $request->setOptions($options);

        return $request;
    }
}
