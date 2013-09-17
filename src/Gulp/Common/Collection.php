<?php

namespace Gulp\Common;

use Gulp\Traits\Structural\Collection\HasDataTrait,
    Gulp\Traits\Structural\Collection\RegistryTrait,
    Gulp\Traits\Structural\Collection\CountableTrait,
    Gulp\Traits\Structural\Collection\AssociativeArrayAccessTrait;

class Collection implements \Countable, \ArrayAccess
{
    use HasDataTrait, RegistryTrait, CountableTrait, AssociativeArrayAccessTrait;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
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
