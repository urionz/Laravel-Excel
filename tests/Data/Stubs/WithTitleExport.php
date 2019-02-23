<?php

namespace urionz\Excel\Tests\Data\Stubs;

use urionz\Excel\Concerns\WithTitle;
use urionz\Excel\Concerns\Exportable;

class WithTitleExport implements WithTitle
{
    use Exportable;

    /**
     * @return string
     */
    public function title(): string
    {
        return 'given-title';
    }
}
