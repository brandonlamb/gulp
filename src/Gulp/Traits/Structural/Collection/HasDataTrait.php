<?php

namespace Gulp\Traits\Structural\Collection;

trait HasDataTrait
{
	/** @var array */
    protected $data = [];

    /**
     * Get the array to operate on from the implementation
     * @return array
     */
    protected function & getData()
    {
        return $this->data;
    }

    /**
     * Set the array to operate on from the implementation
     * @param array $data
     * @return self
     */
    protected function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }
}
