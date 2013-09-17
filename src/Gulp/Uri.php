<?php

namespace Gulp;

class Uri
{
    /** @var array */
    protected $parts = [];

    /**
     * Constructor
     * @param mixed $uri
     * Supports passing of a URI string which will be parsed, an array of an already
     * parsed URI, or another Uri object to get parsed parts from
     */
    public function __construct($uri = null)
    {
        if (empty($uri)) {
            return;
        }

        if (is_string($uri)) {
            $this->parts = parse_url($uri);
            if (!empty($this->parts['query'])) {
                $query = array();
                parse_str($this->parts['query'], $query);
                $this->parts['query'] = $query;
            }
            return;
        }

        if ($uri instanceof static) {
            return $this->parts($uri->parts());
        }

        if (is_array($uri)) {
            return $this->parts($uri);
        }
    }

    public function __set($name, $value)
    {
        $this->parts[$name] = $value;
    }

    public function & __get($name)
    {
        return $this->parts[$name];
    }

    public function __isset($name)
    {
        return isset($this->parts[$name]);
    }

    public function __unset($name)
    {
        unset($this->parts[$name]);
    }

    public function __toString()
    {
        return $this->build();
    }

    /**
     * Get or set the parts
     * @param array $parts
     * @return array
     */
    public function parts(array $parts = null)
    {
        null !== $parts && $this->parts = $parts;
        return $this->parts;
    }

    public function build()
    {
        $uri = '';
        $parts = $this->parts;

        if (!empty($parts['scheme'])) {
            $uri .= $parts['scheme'] . ':';
            if (!empty($parts['host'])) {
                $uri .= '//';
                if (!empty($parts['user'])) {
                    $uri .= $parts['user'];
                    !empty($parts['pass']) && $uri .= ':' . $parts['pass'];
                    $uri .= '@';
                }
                $uri .= $parts['host'];
            }
        }

        !empty($parts['port']) && $uri .= ':' . $parts['port'];
        !empty($parts['path']) && $uri .= $parts['path'];
        !empty($parts['query']) && $uri .= '?' . (is_array($parts['query']) ? http_build_query($parts['query']) : $parts['query']);
        !empty($parts['fragment']) && $uri .= '#' . $parts['fragment'];

        return $uri;
    }

    /**
     * Resolve the passed uri using any options already set
     * @param string $uri
     * @return \Gulp\Uri
     */
    public function resolve($uri)
    {
        $newUri = new static($this);
        return $newUri->extend($uri);
    }

    /**
     * Extend the URI that was set when instantiating a request
     * @param \Gulp\Uri|string $uri
     * @return self
     */
    public function extend($uri)
    {
        !$uri instanceof static && $uri = new static($uri);

        $this->parts = array_merge(
            $this->parts,
            array_diff_key($uri->parts(), array_flip(array('query', 'path')))
        );

        !empty($uri->parts['query']) && $this->extendQuery($uri->parts['query']);
        !empty($uri->parts['path']) && $this->extendPath($uri->parts['path']);

        return $this;
    }

    /**
     * Extend the query parameters
     * @param array $params
     * @return self
     */
    public function extendQuery(array $params)
    {
        $query = empty($this->parts['query']) ? array() : $this->parts['query'];
        $params = empty($params) ? array() : $params;
        $this->parts['query'] = array_merge($query, $params);

        return $this;
    }

    /**
     * Extend the path, figuring out if there is a base uri set already
     * @param string $path
     * @return self
     */
    public function extendPath($path)
    {
        if (empty($path)) {
            return $this;
        }

        if (!strncmp($path, '/', 1)) {
            $this->parts['path'] = $path;
            return $this;
        }

        if (empty($this->parts['path'])) {
            $this->parts['path'] = '/' . $path;
            return $this;
        }

        $this->parts['path'] = substr($this->parts['path'], 0, strrpos($this->parts['path'], '/') + 1) . $path;

        return $this;
    }
}
