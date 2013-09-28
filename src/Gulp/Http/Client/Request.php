<?php

namespace Gulp\Http\Client;

use Gulp\Curl\Wrapper,
    Gulp\Client,
    Gulp\Http\Uri,
    Gulp\Exception;

class Request
{
    const VERSION           = '0.0.1';
    const METHOD_GET        = 'GET';
    const METHOD_POST       = 'POST';
    const METHOD_PUT        = 'PUT';
    const METHOD_DELETE     = 'DELETE';
    const METHOD_HEAD       = 'HEAD';
    const METHOD_OPTIONS    = 'OPTIONS';
    const METHOD_TRACE      = 'TRACE';
    const METHOD_PATCH      = 'PATCH';
    const METHOD_CONNECTION = 'CONNECTION';
    const CONNECT_TIMEOUT   = 30;
    const TIMEOUT           = 30;
    const MAX_REDIRECTS     = 20;

    /** @var string */
    protected $method;

    /** @var \Gulp\Http\Client\Header */
    protected $header;

    /** @var \Gulp\Curl\Wrapper */
    protected $handle;

    /** @var \Gulp\Http\Client\Response */
    protected $response;

    /** @var \Gulp\Client */
    protected $client;

    /** @var array */
    protected $options = [];

    /** @var array */
    protected $postFields = [];

    /**
     * Constructor
     * @param string $method
     * @param string $url
     * @param \Gulp\Curl\Wrapper $handle
     * @param \Gulp\Http\Client\Header $header
     * @param \Gulp\Http\Client\Response $response
     * @param array $options
     * @todo The default options should probably get pushed out of here and up the stack
     */
    public function __construct($method, $url, Wrapper $handle, Header $header, Response $response)
    {
        $this->method = strtoupper($method);

        $this
            ->setResource($handle)
            ->setResource($header)
            ->setResource($response);

        $this->setOptions([
            CURLOPT_CUSTOMREQUEST   => $this->method,
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_AUTOREFERER     => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_FORBID_REUSE    => false,
            CURLOPT_MAXREDIRS       => static::MAX_REDIRECTS,
            CURLOPT_HEADER          => true,
            CURLOPT_PROTOCOLS       => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT  => static::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT         => static::TIMEOUT,
            CURLOPT_ENCODING        => '',
        ]);

        if ($this->method === static::METHOD_GET || $this->method === static::METHOD_HEAD || $this->method === static::METHOD_DELETE) {
            $this->setOptions([CURLOPT_HTTPGET => true, CURLOPT_POST => false]);
            $method !== static::METHOD_GET && $this->setOption(CURLOPT_NOBODY, true);
        } else {
            $this->setOptions([CURLOPT_HTTPGET => false, CURLOPT_POST => true]);
        }
    }

    /**
     * Close the curl handle on object destruct
     */
    public function __destruct()
    {
        $this->handle()->close();
    }

    /**
     * Clone this request
     */
    public function __clone()
    {
        $request = new static;
        $request->setHandle(curl_copy_handle($this->handle));
        return $request;
    }

    /**
     * Resource injection
     * @param Wrapper|Header|Response $resource
     * @return self
     */
    public function setResource($resource)
    {
        if ($resource instanceof Wrapper) {
            $this->handle = $resource;
        } elseif ($resource instanceof Header) {
            $this->header = $resource;
        } elseif ($resource instanceof Response) {
            $this->response = $resource;
        } elseif ($resource instanceof Client) {
            $this->client = $resource;
        } else {
            throw new \InvalidArgumentException('Unknown resource');
        }

        return $this;
    }

    /**
     * Get the method
     * @return string
     */
    public function method()
    {
        return (string) $this->method;
    }

    /**
     * Get the header
     * @return \Gulp\Header
     */
    public function header()
    {
        return $this->header;
    }

    /**
     * Get the curl handle
     * @return \Gulp\Curl\Wrapper
     */
    public function handle()
    {
        return $this->handle;
    }

   /**
     * Get the response
     * @return \Gulp\Http\Client\Response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Get the client
     * @return \Gulp\Http\Client
     */
    public function client()
    {
        return $this->client;
    }

    /**
     * Set a curl option
     * @param int $option
     * @param mixed $value
     * @return self
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * Set multiple curl options, overwriting any existing
     * @param array $options
     * @return self
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }
        return $this;
    }

    /**
     * Set a header value
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setHeader($key, $value)
    {
        $this->header()->set($key, $value);
        return $this;
    }

    /**
     * Set an array of headers
     * @param array $headers
     * @return self
     */
    public function setHeaders(array $headers)
    {
        $this->header()->addMultiple($headers);
        return $this;
    }

    /**
     * Add post fields
     * @param array $params
     * @return self
     */
    public function addPostFields($params)
    {
        foreach ($params as $key => $value) {
            if (is_string($key) && is_string($value) && $value{0} === '@') {
                $this->addPostFile($key, $value);
                continue;
            }
            $this->postFields[$key] = $value;
        }

        return $this;
    }

    /**
     * Add post file upload
     * @param string $key
     * @param string $value
     * @return self
     */
    public function addPostFile($key, $value)
    {
        $this->header()->set('Content-Type', 'multipart/form-data');
        return $this->addPostFields([$key => curl_file_create(substr($value, 1), null, $key)]);
    }

    /**
     * Use this method to make requests to pages that requires prior HTTP authentication.
     * @param string $username User name to be used for authentication.
     * @param string $password Password to be used for authentication.
     * @param string $type (Optional) The HTTP authentication method(s) to use. The options are:
     * CURLAUTH_BASIC, CURLAUTH_DIGEST, CURLAUTH_GSSNEGOTIATE, CURLAUTH_NTLM, CURLAUTH_ANY, CURLAUTH_ANYSAFE
     * The bitwise | (or) operator can be used to combine more than one method. If this is done, cURL will poll the
     * server to see what methods it supports and pick the best one.
     * @return self
     */
    public function setAuth($username, $password, $type = CURLAUTH_ANY)
    {
        // set the required options
        $this->handle()
            ->setHttpAuth($type)
            ->setUserPwd("$username:$password");

        return $this;
    }

    public function setProxy($host = null, $port = 8080, $username = null, $password = null)
    {
        if (null === $host) {
            $this->handle()
                ->setHttpProxyTunnel(null)
                ->setProxyAuth(null)
                ->setProxy(null)
                ->setProxyPort(null)
                ->setProxyType(null);
            return $this;
        }

        $this->handle()
            ->setHttpProxyTunnel(true)
            ->setProxyAuth(CURLAUTH_BASIC)
            ->setProxy($host)
            ->setProxyPort($port)
            ->setProxyType(CURLPROXY_HTTP);

        if (null !== $username) {
            $pair = $username;
            null !== $password && $pair .= ':' . $pass;
            $this->handle()->setProxyUserPwd($pair);
        }

        return $this;
    }

    public function setSsl($verifyPeer = false, $verifyHost = 2, $caFile = null, $path = null)
    {
        // set default options
        $this->setOptions([
            CURLOPT_SSL_VERIFYPEER => $verifyPeer,
            CURLOPT_SSL_VERIFYHOST => $verifyPost,
        ]);

        // if a path to a file holding one or more certificates to verify the peer with was given
        if (null !== $file) {
            // if file could not be found, throw exception
            if (!is_file($file)) {
                throw new \RuntimeException('File "' . $file . '", holding one or more certificates to verify the peer with, was not found!');
            }
            $this->setOption(CURLOPT_CAINFO, $file);
        }

        // if a directory holding multiple CA certificates was given
        if (null !== $path) {
            // if folder could not be found, throw exception
            if (!is_dir($path)) {
                throw new \RuntimeException('Directory "' . $path . '", holding one or more CA certificates to verify the peer with, was not found!');
            }
            $this->setOption(CURLOPT_CAPATH, $path);
        }
    }

    public function setCookies($path)
    {
        // file does not exist
        if (!is_writable($path)) {
            // attempt to create it
            if (!($handle = fopen($path, 'a'))) {
                throw new \RuntimeException('File "' . $path . '" for storing cookies could not be found nor could it automatically be created! Make sure either that the path to the file points to a writable directory, or create the file yourself and make it writable.');
            }

            // if file could be create, release handle
            fclose($handle);
        }

        // set these options
        $this->handle()
            ->setCookieFile($path)
            ->setCookieJar($path);

        return $this;
    }

    /**
     * Set the raw post body
     * @param mixed $body
     * @return self
     */
    public function setBody($body)
    {
        $this->postFields = [];
        $this->handle()->setOption(CURLOPT_POSTFIELDS, $body);
        return $this;
    }

    /**
     * Excecute the curl call and return the response
     * @return \Gulp\Http\Client\Response
     */
    public function send()
    {
        $headers = count($this->header()) > 0 ? $this->header()->build() : [];
        $headers[] = 'Expect:';
        $this->setOption(CURLOPT_HTTPHEADER, $headers);

        // Set the options all at once
        $this->handle()->setOptions($this->options);

        // If there are post or file uploads, add to post fields
        if (!empty($this->postFields) && is_array($this->postFields)) {
            $this->handle()->setPostFields($this->postFields);
        }

        // Excecute the curl call and assign the response to the response body
        $body = $this->handle()->execute();

        // Check if any errors occurred
        if ($errno = $this->handle()->errorNo) {
            throw new Exception($this->handle()->error, $errno);
        }

        // Get the header size so we know where the body begins in the response
        $headerSize = $this->handle()->getInfo(CURLINFO_HEADER_SIZE);

        // Parse out headers from the body
        $this->response()
            ->setHeaders(substr($body, 0, $headerSize))
            ->setBody(substr($body, $headerSize));

        return $this->response;
    }
}
