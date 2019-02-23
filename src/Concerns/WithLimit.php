<?php

namespace urionz\Excel\Concerns;

interface WithLimit
{
    /**
     * @return int
     */
    public function limit(): int;
}
