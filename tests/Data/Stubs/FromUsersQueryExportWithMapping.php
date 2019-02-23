<?php

namespace urionz\Excel\Tests\Data\Stubs;

use Illuminate\Database\Query\Builder;
use urionz\Excel\Concerns\FromQuery;
use urionz\Excel\Events\BeforeSheet;
use urionz\Excel\Concerns\Exportable;
use urionz\Excel\Concerns\WithEvents;
use urionz\Excel\Concerns\WithMapping;
use urionz\Excel\Tests\Data\Stubs\Database\User;

class FromUsersQueryExportWithMapping implements FromQuery, WithMapping, WithEvents
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
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class   => function (BeforeSheet $event) {
                $event->sheet->chunkSize(10);
            },
        ];
    }

    /**
     * @param User $row
     *
     * @return array
     */
    public function map($row): array
    {
        return [
            'name' => $row->name,
        ];
    }
}
