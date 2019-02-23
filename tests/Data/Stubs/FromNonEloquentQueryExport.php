<?php

namespace urionz\Excel\Tests\Data\Stubs;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use urionz\Excel\Concerns\FromQuery;
use urionz\Excel\Concerns\Exportable;
use urionz\Excel\Concerns\WithCustomChunkSize;

class FromNonEloquentQueryExport implements FromQuery, WithCustomChunkSize
{
    use Exportable;

    /**
     * @return Builder
     */
    public function query()
    {
        return DB::table('users')->select('name')->orderBy('id');
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 10;
    }
}
