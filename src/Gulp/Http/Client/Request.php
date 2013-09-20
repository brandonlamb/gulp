<?php

namespace Gulp\Http\Client;

use Gulp\Curl\Wrapper,
    Gulp\Client;

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

    /** @var string */
    protected $responseClass = '\\Gulp\\Http\\Client\\Response';

    /** @var string */
    protected $wrapperClass = '\\Gulp\\Curl\\Wrapper';

    /**
     * Constructor
     * @param string $method
     * @param string $url
     * @param \Gulp\Http\Client\Header $header
     * @param array $options
     */
    public function __construct($method, $url, Header $header)
    {
        $this
            ->setHeader($header)
            ->setResponse(new $this->responseClass())
            ->setHandle(new $this->wrapperClass());

        $this->setOptions([
            CURLOPT_CUSTOMREQUEST   => $method,
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

        if ($method === static::METHOD_GET || $method === static::METHOD_HEAD || $method === static::METHOD_DELETE) {
            $this->setOptions([CURLOPT_HTTPGET => true, CURLOPT_POST => false]);
            $method !== static::METHOD_GET && $this->setOption(CURLOPT_NOBODY, true);
        } else {
            $this->setOptions([CURLOPT_HTTPGET => false, CURLOPT_POST => true]);
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->getHandle()->close();
    }

    /**
     * Clone this request
     */
    public function __clone()
    {
        $request = new static;
        $request->handle(curl_copy_handle($this->handle));
        return $request;
    }

    /**
     * Add post fields
     * @param array $params
     * @return self
     */
    public function addPostFields($params)
    {
        foreach ($params as $key => $value) {
            if (is_string($key) && $value{0} === '@') {
                $value = curl_file_create(substr($value, 1), null, $key);
            }
            $this->postFields[$key] = $value;
        }

        if (!empty($params) && is_array($params)) {
            $this->setOption(CURLOPT_POSTFIELDS, $multiPart ? $params : http_build_query($params));
        }
        return $this;
    }

    /**
     * Set the client
     * @param \Gulp\Client $client
     * @return self
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Get the client
     * @return \Gulp\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Get or set the header
     * @param \Gulp\Header $header
     * @return self
     */
    public function setHeader(Header $header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * Get the header
     * @return \Gulp\Header
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Set the response
     * @param \Gulp\Http\Client\Response $response
     * @return self
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Set the curl handle
     * @param \Gulp\Curl\Wrapper $handle
     * @return self
     */
    public function setHandle(Wrapper $handle)
    {
        $this->handle = $handle;
        return $this;
    }

    /**
     * Get the curl handle
     * @return \Gulp\Curl\Wrapper
     */
    public function getHandle()
    {
        return $this->handle;
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
        $this->getHandle()
            ->setHttpAuth($type)
            ->setUserPwd("$username:$password");

        return $this;
    }

    public function setProxy($host = null, $port = 8080, $username = null, $password = null)
    {
        if (null === $host) {
            $this->getHandle()
                ->setHttpProxyTunnel(null)
                ->setProxyAuth(null)
                ->setProxy(null)
                ->setProxyPort(null)
                ->setProxyType(null);
            return $this;
        }

        $this->getHandle()
            ->setHttpProxyTunnel(true)
            ->setProxyAuth(CURLAUTH_BASIC)
            ->setProxy($host)
            ->setProxyPort($port)
            ->setProxyType(CURLPROXY_HTTP);

        if (null !== $username) {
            $pair = $username;
            null !== $password && $pair .= ':' . $pass;
            $this->getHandle()->setProxyUserPwd($pair);
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
        $this->getHandle()
            ->setCookieFile($path)
            ->setCookieJar($path);

        return $this;
    }

    /**
     * Excecute the curl call and return the response
     * @return \Gulp\Http\Client\Response
     */
    public function send()
    {
        $header = count($this->getHeader()) > 0 ? $this->getHeader()->build() : [];
        $header[] = 'Expect:';
        $this->setOption(CURLOPT_HTTPHEADER, $header);

        // Set the options all at once
        curl_setopt_array($this->handle, $this->options);

        // Excecute the curl call and assign the response to the response body
        $body = curl_exec($this->handle);

        // Check if any errors occurred
        if ($errno = curl_errno($this->handle)) {
            throw new Exception(curl_error($this->handle), $errno);
        }

        // Get the header size so we know where the body begins in the response
        $headerSize = curl_getinfo($this->handle, CURLINFO_HEADER_SIZE);

        // Parse out headers from the body
        $this->response
            ->setHeaders(substr($body, 0, $headerSize))
            ->setBody(substr($body, $headerSize));

        return $this->response;
    }
}
