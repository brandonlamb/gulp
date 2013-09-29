<?php

namespace Gulp;

use Gulp\Traits\RegistryTrait,
    Gulp\Traits\AssociativeArrayAccessTrait;

class Collection implements \Countable, \ArrayAccess
{
    use RegistryTrait, AssociativeArrayAccessTrait;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Fetch a bag object in the Registry
     * @param string $bag The data bag, assuming an array
     * @return array
     */
    public function & getBag($bag)
    {
        $data =& $this->getData();
        !isset($data[$bag]) && $data[$bag] = [];
        return $data[$bag];
    }

    /**
     * Get an object/value out of the Registry
     * @param string $key The key of the object/value to retrieve
     * @param mixed $default The value to return if the key is missing
     * @return mixed The object/value from the Registry
     */
    public function get($bag, $key, $default = null)
    {
        return $this->has($bag) ? $this->getBag($bag)[$key] : $default;
    }

    /**
     * Store an object/value in the Registry
     * @param string $key The key of the object/value being set
     * @param mixed $value The object/value to store
     * @return self The instance of the Registry for chaining
     */
    public function set($bag, $key, $value)
    {
        $data =& $this->getBag($bag);
        $data[$key] = $value;
        return $this;
    }

    /**
     * Merges two or more arrays into one recursively. If each array has an element with the same string key value,
     * the latter will overwrite the former (different from array_merge_recursive). Recursive merging will be conducted
     * if both arrays have an element of array type and are having the same key. For integer-keyed elements,
     * the elements from the latter array will be appended to the former array.
     *
     * First parameter must be array to be merged to, second and more parameters should be arrays to be merged from.
     * You can specifiy additional arrays via 3rd, 4th argument, etc.
     * @return array the merged array (the original arrays are not changed.)
     */
    public static function mergeArray()
    {
        $args = func_get_args();
        $res = array_shift($args);

        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if (is_integer($k)) {
                    isset($res[$k]) ? $res[] = $v : $res[$k] = $v;
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = static::mergeArray($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }
}
