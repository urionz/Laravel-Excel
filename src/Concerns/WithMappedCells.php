<?php

namespace urionz\Excel\Concerns;

interface WithMappedCells
{
    /**
     * @return array
     */
    public function mapping(): array;
}
