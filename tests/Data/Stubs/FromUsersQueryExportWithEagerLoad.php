<?php

namespace urionz\Excel\Tests\Data\Stubs;

use Illuminate\Database\Query\Builder;
use urionz\Excel\Concerns\FromQuery;
use urionz\Excel\Concerns\Exportable;
use urionz\Excel\Concerns\WithMapping;
use urionz\Excel\Tests\Data\Stubs\Database\User;

class FromUsersQueryExportWithEagerLoad implements FromQuery, WithMapping
{
    use Exportable;

    /**
     * @return Builder
     */
    public function query()
    {
        return User::query()->with('groups')->withCount('groups');
    }

    /**
     * @param mixed $row
     *
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->name,
            $row->groups_count,
            $row->groups->implode('name', ', '),
        ];
    }
}
