<?php

namespace Gulp\Traits;

trait AssociativeArrayAccessTrait
{
    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new \OutOfBoundsException("The offset \"{$offset}\" does not exist.");
        }

        return $this->getData()[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return self
     */
    public function offsetSet($offset, $value)
    {
        $data =& $this->getData();
        $data[$offset] = $value;
        return $this;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->getData());
    }

    /**
     * @param mixed $offset
     * @return self
     */
    public function offsetUnset($offset)
    {
        $data =& $this->getData();
        unset($data[$offset]);
        return $this;
    }
}
