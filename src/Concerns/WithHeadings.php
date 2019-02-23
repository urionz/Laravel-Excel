<?php

namespace urionz\Excel\Concerns;

interface WithHeadings
{
    /**
     * @return array
     */
    public function headings(): array;
}
