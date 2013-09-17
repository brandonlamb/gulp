<?php

namespace Gulp;

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
    const METHOD_CONNECTION = 'CONNECTION';
    const CONNECT_TIMEOUT   = 30;
    const TIMEOUT           = 30;
    const MAX_REDIRECTS     = 20;

    /** @var \Gulp\Header */
    protected $header;

    /** @var \Gulp\Uri */
    protected $baseUri;

    /** @var curl */
    protected $handle;

    /** @var array */
    protected $options = [];

    /**
     * Constructor
     * @param string $url
     * @param \Gulp\Header $header
     * @param \Gulp\Uri $uri
     */
    public function __construct($url = null, Header $header = null, Uri $uri = null)
    {
        $this->curl = new Curl($url);
        $this->handle = curl_init();
        $this->header = null !== $header ? $header : new Header();
        $this->baseUri = null !== $uri ? $uri : new Uri($url);

        $this->initOptions();
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        curl_close($this->handle);
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
     * Initializes default options
     * @return self
     */
    protected function initOptions()
    {
        $this->options = [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_AUTOREFERER     => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_FORBID_REUSE    => false,
            CURLOPT_MAXREDIRS       => static::MAX_REDIRECTS,
            CURLOPT_HEADER          => true,
            CURLOPT_PROTOCOLS       => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_USERAGENT       => 'Gulp HTTP/' . static::VERSION . ' (Curl)',
            CURLOPT_CONNECTTIMEOUT  => static::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT         => static::TIMEOUT,
            CURLOPT_ENCODING        => '',
        ];

        return $this;
    }

    protected function initPostFields($params)
    {
        $multiPart = false;
        foreach ($params as $param) {
            if (is_string($param) && preg_match('/^@/', $param)) {
                $multiPart = true;
                break;
            }
        }

        if (!empty($params) && is_array($params)) {
            $this->setOption(CURLOPT_POSTFIELDS, $multiPart ? $params : http_build_query($params));
        }
    }

    /**
     * Get or set curl handle
     * @param curl $handle
     * @return self
     */
    public function handle($handle = null)
    {
        null !== $handle && $this->handle = $handle;
        return $this->handle;
    }

    /**
     * Get or set the header
     * @param \Gulp\Header $header
     * @return \Gulp\Header
     */
    public function header(Header $header = null)
    {
        null !== $header && $this->header = $header;
        return $this->header;
    }

    /**
     * Get or set the curl handle
     * @param \Gulp\Curl $curl
     * @return \Gulp\Curl
     */
    public function curl(\Gulp\Curl $curl = null)
    {
        null !== $curl && $this->curl = $curl;
        return $this->curl;
    }

    /**
     * Get or set the uri
     * @param \Gulp\Uri $uri
     * @return \Gulp\Uri
     */
    public function uri(Uri $uri = null)
    {
        null !== $uri && $this->baseUri = $uri;
        return $this->baseUri;
    }

    public function setBaseUri($baseUri)
    {
        $this->baseUri = new Uri($baseUri);
    }

    public function getBaseUri()
    {
        return $this->baseUri->toString();
    }

    public function resolveUri($uri)
    {
        return $this->baseUri->resolve($uri);
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
        foreach ($options as $option => $value) {
            $this->options[$option] = $value;
        }
        return $this;
    }

    public function get($uri = null, array $headers = array(), array $params = array())
    {
        $uri = $this->resolveUri($uri);
        !empty($params) && $uri->extendQuery($params);
        $this->headers->addMultiple($headers);

        $this->setOptions(array(
           CURLOPT_URL => $uri->build(),
           CURLOPT_HTTPGET => true,
           CURLOPT_CUSTOMREQUEST => static::METHOD_GET,
        ));

        return $this;
    }

    public function head($uri = null, $headers = array(), $params = array())
    {
        $uri = $this->resolveUri($uri);
        !empty($params) && $uri->extendQuery($params);

        $this->setOptions(array(
            CURLOPT_URL => $uri->build(),
            CURLOPT_HTTPGET => true,
            CURLOPT_CUSTOMREQUEST => static::METHOD_HEAD,
            CURLOPT_NOBODY =>  true,
        ));

        return $this;
    }

    public function delete($uri = null, $headers = array(), $params = array())
    {
        $uri = $this->resolveUri($uri);
        !empty($params) && $uri->extendQuery($params);

        $this->setOptions(array(
            CURLOPT_URL => $uri->build(),
            CURLOPT_HTTPGET => true,
            CURLOPT_CUSTOMREQUEST => static::METHOD_DELETE,
            CURLOPT_NOBODY =>  true,
        ));

        return $this;
    }

    public function post($uri = null, $headers = array(), $params = array())
    {
        $this->header->set('Content-Type', 'application/x-www-form-urlencoded');

        $this->setOptions(array(
            CURLOPT_URL => $this->resolveUri($uri),
            CURLOPT_POST => true,
            CURLOPT_CUSTOMREQUEST => static::METHOD_POST,
        ));

        $this->initPostFields($params);

        return $this;
    }

    public function put($uri = null, $headers = array(), $params = array())
    {
        $this->setOptions(array(
            CURLOPT_URL => $this->resolveUri($uri),
            CURLOPT_POST => true,
            CURLOPT_CUSTOMREQUEST => static::METHOD_PUT,
        ));

        $this->initPostFields($params);

        return $this;
    }

    public function options($uri = null, $headers = array(), $params = array())
    {
        $this->setOptions(array(
            CURLOPT_URL => $this->resolveUri($uri),
            CURLOPT_POST => true,
            CURLOPT_CUSTOMREQUEST => static::METHOD_OPTIONS,
        ));

        $this->initPostFields($params);

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
        $this->setOptions(array(
            CURLOPT_HTTPAUTH => $type,
            CURLOPT_USERPWD => "$username:$password",
        ));
        return $this;
    }

    public function setProxy($host = null, $port = 8080, $username = null, $password = null)
    {
        if (null === $host) {
            $this->option(array(
                CURLOPT_HTTPPROXYTUNNEL =>  null,
                CURLOPT_PROXY =>  null,
                CURLOPT_PROXYPORT =>  null,
            ));
            return $this;
        }

        // set the required options
        $this->option(array(
            CURLOPT_HTTPPROXYTUNNEL =>  true,
            CURLOPT_PROXY =>  $host,
            CURLOPT_PROXYPORT =>  $port,
        ));

        if (null !== $username) {
            $pair = $username;
            null !== $password && $pair .= ':' . $pass;
            $this->setOption(CURLOPT_PROXYUSERPWD, $pair);
        }

        // if a username is also specified, set authentication values
        null !== $username && $this->option(CURLOPT_PROXYUSERPWD, $username . ':' . $password);

        return $this;
    }

    public function setSsl($verifyPeer = false, $verifyHost = 2, $caFile = null, $path = null)
    {
        // set default options
        $this->setOptions(array(
            CURLOPT_SSL_VERIFYPEER => $verifyPeer,
            CURLOPT_SSL_VERIFYHOST => $verifyPost,
        ));

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

    public function setCookies($path, $keep = false)
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
        $this->setOptions(array(
            CURLOPT_COOKIEJAR => $path,
            CURLOPT_COOKIEFILE => $path,
        ));

        return $this;
    }

    public function send()
    {
        $header = count($this->header) > 0 ? $this->header->build() : [];
        $header[] = 'Expect:';
        $this->setOption(CURLOPT_HTTPHEADER, $header);

        curl_setopt_array($this->handle, $this->options);

        $content = curl_exec($this->handle);
        if ($errno = curl_errno($this->handle)) {
            throw new Exception(curl_error($this->handle), $errno);
        }
        $headerSize = curl_getinfo($this->handle, CURLINFO_HEADER_SIZE);

        $response = new Response();
        $response->parse($content, $headerSize);
        return $response;
    }
}
