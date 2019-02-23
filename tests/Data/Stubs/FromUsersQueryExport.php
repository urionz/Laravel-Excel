<?php

namespace urionz\Excel\Tests\Data\Stubs;

use Illuminate\Database\Query\Builder;
use urionz\Excel\Concerns\FromQuery;
use urionz\Excel\Concerns\Exportable;
use urionz\Excel\Concerns\WithCustomChunkSize;
use urionz\Excel\Tests\Data\Stubs\Database\User;

class FromUsersQueryExport implements FromQuery, WithCustomChunkSize
{
    use Exportable;

    /**
     * @return Builder
     */
    public function query()
    {
        return User::query();
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 10;
    }
}
