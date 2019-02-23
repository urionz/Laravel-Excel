<?php

namespace urionz\Excel\Tests\Data\Stubs;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use urionz\Excel\Concerns\FromQuery;
use urionz\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use urionz\Excel\Concerns\WithMapping;
use urionz\Excel\Concerns\WithCustomQuerySize;
use urionz\Excel\Tests\Data\Stubs\Database\Group;

class FromQueryWithCustomQuerySize implements FromQuery, WithCustomQuerySize, WithMapping, ShouldQueue
{
    use Exportable;

    /**
     * @return Builder
     */
    public function query()
    {
        $query = Group::with('users')
            ->join('group_user', 'groups.id', '=', 'group_user.group_id')
            ->select('groups.*', DB::raw('count(group_user.user_id) as number_of_users'))
            ->groupBy('groups.id')
            ->orderBy('number_of_users');

        return $query;
    }

    /**
     * @return int
     */
    public function querySize(): int
    {
        return Group::has('users')->count();
    }

    /**
     * @param Group $row
     *
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->number_of_users,
        ];
    }
}
