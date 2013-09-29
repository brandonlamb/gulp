<?php

namespace Gulp\Http;

use Gulp\Traits\RegistryTrait,
    Gulp\Traits\AssociativeArrayAccessTrait;

class Header implements \Countable, \ArrayAccess
{
    use RegistryTrait, AssociativeArrayAccessTrait;

    const BUILD_STATUS = 1;
    const BUILD_FIELDS = 2;

    public $version = '1.0';
    public $statusCode = 0;
    public $statusMessage = '';
    public $status = '';

    protected static $messages = [
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    ];

    public function __construct(array $headers = [])
    {
        $this->data = $headers;
    }

    public function addMultiple($data)
    {
        $this->data = array_combine($this->data, $data);
    }

    public function parse($content)
    {
        if (empty($content)) {
            return false;
        }

        if (is_string($content)) {
            $content = array_filter(explode("\r\n", $content));
        } elseif (!is_array($content)) {
            return false;
        }

        $status = [];
        if (preg_match('/^HTTP\/(\d(?:\.\d)?)\s+(\d{3})\s+(.+)$/i', $content[0], $status)) {
            $this->status = array_shift($content);
            $this->version = $status[1];
            $this->statusCode = intval($status[2]);
            $this->statusMessage = $status[3];
        }

        foreach ($content as $field) {
            !is_array($field) && $field = array_map('trim', explode(':', $field));
            count($field) == 2 && $this->set($field[0], $field[1]);
        }

        return true;
    }

    public function build($flags = 0)
    {
        $lines = [];
        if (($flags & self::BUILD_STATUS) && !empty(self::$messages[$this->statusCode])) {
            $lines[] = 'HTTP/' . $this->version . ' ' . $this->statusCode . ' ' . self::$messages[$this->statusCode];
        }

        foreach ($this->data as $field => $value) {
            $lines[] = $field . ': ' . $value;
        }

        if ($flags & self::BUILD_FIELDS) {
            return implode("\r\n", $lines);
        }

        return $lines;
    }
}
