<?php

namespace Gulp\Http\Client;

class Response
{
    /** @var string */
    protected $body;

    /** @var \Gulp\Header */
    protected $header;

    public function __construct(Header $header = null)
    {
        $this->header = null !== $header ? $header : new Header();
    }

    public function header()
    {
        return $this->header;
    }

    public function body()
    {
        return (string) $this->body;
    }

    public function json()
    {
        return $this->json;
    }

    public function parse($content, $size)
    {
        $this->header->parse(substr($content, 0, $size));
        $this->body = substr($content, $size);
        $this->json = json_decode($this->body);
    }

}
