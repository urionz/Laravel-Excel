<?php

namespace urionz\Excel\Tests\Data\Stubs;

use urionz\Excel\Concerns\Exportable;
use urionz\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Collection;
use urionz\Excel\Concerns\FromCollection;
use urionz\Excel\Tests\Data\Stubs\Database\User;

class EloquentCollectionWithMappingExport implements FromCollection, WithMapping
{
    use Exportable;

    /**
     * @return Collection
     */
    public function collection()
    {
        return collect([
            new User([
                'firstname' => 'Patrick',
                'lastname'  => 'Brouwers',
            ]),
        ]);
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function map($user): array
    {
        return [
            $user->firstname,
            $user->lastname,
        ];
    }
}
