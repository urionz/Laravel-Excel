<?php

namespace urionz\Excel\Concerns;

use urionz\Excel\Row;

interface OnEachRow
{
    /**
     * @param Row $row
     */
    public function onRow(Row $row);
}
