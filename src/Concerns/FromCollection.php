<?php

namespace urionz\Excel\Concerns;

use Illuminate\Support\Collection;

interface FromCollection
{
    /**
     * @return Collection
     */
    public function collection();
}
