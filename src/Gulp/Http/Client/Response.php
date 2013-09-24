<?php

namespace Gulp\Http\Client;

class Response
{
    /** @var string */
    protected $body;

    /** @var \Gulp\Header */
    protected $header;

    /** @var int */
    protected $headerSize = 0;

    /**
     * @param \Gulp\Http\Client\Header $header
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
        $this->getHeader()->parse($headers);
        return $this;
    }

    /**
     * Get the header
     * @return \Gulp\Http\Client\Header
     */
    public function getHeader()
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
    public function getBody()
    {
        return (string) $this->body;
    }

    /**
     * Return the json_decode parsed response body
     * @return mixed
     */
    public function json()
    {
        null === $this->json && $this->json = json_decode();
        return $this->json;
    }
}
