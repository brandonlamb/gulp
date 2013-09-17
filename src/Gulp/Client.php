<?php

namespace Gulp;

use Gulp\Common\Collection,
	Gulp\Http\Uri,
	Gulp\Http\Client\Request,
	Gulp\Http\Client\Header,
	Gulp\Curl\Version as CurlVersion;

class Client
{
    const VERSION = '0.0.1';
	const REQUEST_OPTIONS = 'request.options.';

	/** @var string */
	protected $baseUrl;

	/** @var array */
	protected $config;

	/** @var string */
	protected $userAgent;

	/** @var Uri */
	protected $uri;

	/**
	 * @param string $baseUrl
	 * @param array|Collection
	 */
	public function __construct($baseUrl = '', $config = null)
	{
		$this->setConfig($config ?: new Collection());
        $this->setBaseUrl($baseUrl);
		$this->setUserAgent('', true);
		$this->uri = new Uri($this->getBaseUrl());
	}

	/**
	 * Sets the config using either an array or instantiated Collection
	 * @param array|Collection
	 * @return self
	 */
 	final public function setConfig($config)
    {
        if ($config instanceof Collection) {
            $this->config = $config;
        } elseif (is_array($config)) {
            $this->config = new Collection($config);
        } else {
            throw new \InvalidArgumentException('Config must be an array or Collection');
        }

        return $this;
    }

    /**
     * Set the base url for all created requests
     * @param string $url
     * @return self
     */
	public function setBaseUrl($url)
	{
		$this->baseUrl = (string) $url;
		return $this;
	}

    /**
     * Get the base url for all created requests
     * @return string
     */
	public function getBaseUrl()
    {
        return $this->baseUrl;
    }

	/**
	 * Set the user agent for all created requests
	 * @param string $userAgent
	 * @param bool $includeDefault
	 * @return self
	 */
	public function setUserAgent($userAgent, $includeDefault = false)
    {
        $includeDefault && $userAgent .= ' ' . $this->getDefaultUserAgent();
        $this->userAgent = $userAgent;
        return $this;
    }

 	/**
     * Get the default User-Agent string to use with Guzzle
     *
     * @return string
     */
    public function getDefaultUserAgent()
    {
        return 'Gulp/' . static::VERSION
            . ' curl/' . CurlVersion::getInstance()->get('version')
            . ' PHP/' . PHP_VERSION;
    }

    /**
     * Set a default request option on the client that will be used as a default for each request
     * @param string $bag request.options key (e.g. allow_redirects) or path to a nested key (e.g. headers/foo)
     * @param string|array $key
     * @param mixed $value Value to set
     * @return $this
     */
    public function setDefaultOption($bag, $key, $value = null)
    {
    	if (is_array($key)) {
    		foreach ($key as $k => $v) {
    			$this->config->set(static::REQUEST_OPTIONS . $bag, $k, $v);
    		}
    	} else {
        	$this->config->set(static::REQUEST_OPTIONS . $bag, $key, $value);
    	}
        return $this;
    }

    /**
     * Retrieve a default request option from the client
     * @param string $keyOrPath request.options key (e.g. allow_redirects) or path to a nested key (e.g. headers/foo)
     * @return mixed|null
     */
    public function getDefaultOption($bag, $key)
    {
        return $this->config->get(static::REQUEST_OPTIONS . $bag, $key);
    }

    /**
     * Create a GET request
     * @param string $uri
     * @param array $headers
     * @param array $options
     * @return \Gulp\Request
     */
	public function get($uri = null, array $headers = [], array $options = [])
    {
        return $this->createRequest('GET', $uri, $headers, null, $options);
    }

    /**
     * Create a HEAD request
     * @param string $uri
     * @param array $headers
     * @param array $options
     * @return \Gulp\Request
     */
    public function head($uri = null, array $headers = [], array $options = [])
    {
        return $this->createRequest('HEAD', $uri, $headers, null, $options);
    }

    /**
     * Create a DELETE request
     * @param string $uri
     * @param array $headers
     * @param string $body
     * @param array $options
     * @return \Gulp\Request
     */
    public function delete($uri = null, array $headers = [], $body = null, array $options = [])
    {
        return $this->createRequest('DELETE', $uri, $headers, $body, $options);
    }

    /**
     * Create a PUT request
     * @param string $uri
     * @param array $headers
     * @param string $body
     * @param array $options
     * @return \Gulp\Request
     */
    public function put($uri = null, array $headers = [], $body = null, array $options = [])
    {
        return $this->createRequest('PUT', $uri, $headers, $body, $options);
    }

    /**
     * Create a PATH request
     * @param string $uri
     * @param array $headers
     * @param string $body
     * @param array $options
     * @return \Gulp\Request
     */
    public function patch($uri = null, array $headers = [], $body = null, array $options = [])
    {
        return $this->createRequest('PATCH', $uri, $headers, $body, $options);
    }

    /**
     * Create a POST request
     * @param string $uri
     * @param array $headers
     * @param string $body
     * @param array $options
     * @return \Gulp\Request
     */
    public function post($uri = null, array $headers = [], $postBody = null, array $options = [])
    {
        return $this->createRequest('POST', $uri, $headers, $postBody, $options);
    }

    /**
     * Create an OPTIONS request
     * @param string $uri
     * @param array $options
     * @return \Gulp\Request
     */
    public function options($uri = null, array $options = [])
    {
        return $this->createRequest('OPTIONS', $uri, $options);
    }

    /**
     * Create a request object
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param string $body
     * @param array $options
     */
	public function createRequest($method = 'GET', $uri = null, $headers = null, $body = null, array $options = [])
    {
    	$url = $this->uri->resolve($uri)->build();
		$defaultHeaders = $this->config->getBag(static::REQUEST_OPTIONS . 'headers');

        // If default headers are provided, then merge them under any explicitly provided headers for the request
        if (count($defaultHeaders)) {
            if (!$headers) {
                $headers = $defaultHeaders;
            } elseif (is_array($headers)) {
                $headers += $defaultHeaders;
            } elseif ($headers instanceof Collection) {
                $headers = $headers->getData() + $defaultHeaders;
            }
        }

        $request = new Request(new Header($headers));
        $request->getHandle()
    		->setUserAgent($this->userAgent)
			->setUrl($url);
        $request->setOptions($options);

d($method, $url, $headers, $body, $options);

        return $this->prepareRequest($this->requestFactory->create($method, (string) $url, $headers, $body), $options);
    }
}
