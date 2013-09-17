<?php

namespace Gulp\Traits\Structural\Collection;

trait CountableTrait
{
    public function count()
    {
        return count($this->getData());
    }
}
