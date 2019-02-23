<?php

namespace urionz\Excel\Concerns;

interface WithEvents
{
    /**
     * @return array
     */
    public function registerEvents(): array;
}
