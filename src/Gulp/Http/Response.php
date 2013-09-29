<?php

namespace Gulp\Http;

class Response
{
    /** @var string */
    protected $body;

    /** @var \Gulp\Header */
    protected $header;

    /** @var int */
    protected $headerSize = 0;

    /**
     * @param \Gulp\Http\Header $header
     */
    public function __construct(Header $header)
    {
        $this->header = $header;
    }

    /**
     * Set the headers
     * @param string $headers
     * @return self
     */
    public function setHeaders($headers)
    {
        $this->header()->parse($headers);
        return $this;
    }

    /**
     * Get the header
     * @return \Gulp\Http\Header
     */
    public function header()
    {
        return $this->header;
    }

    /**
     * Set the response body
     * @var string $body
     * @return self
     */
    public function setBody($body)
    {
        $this->body = (string) $body;
        return $this;
    }

    /**
     * Get the response body
     * @return string
     */
    public function & getBody()
    {
        return (string) $this->body;
    }

    /**
     * Return the json_decode parsed response body
     * @param bool $toArray, parse the response as an array. False returns objects
     * @return mixed
     */
    public function json($toArray = true)
    {
        return json_decode($this->body, (bool) $toArray);
    }
}
