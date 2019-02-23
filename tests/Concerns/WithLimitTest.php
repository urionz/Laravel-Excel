<?php

namespace urionz\Excel\Tests\Concerns;

use PHPUnit\Framework\Assert;
use urionz\Excel\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use urionz\Excel\Concerns\ToArray;
use urionz\Excel\Concerns\ToModel;
use urionz\Excel\Concerns\WithLimit;
use urionz\Excel\Concerns\Importable;
use urionz\Excel\Concerns\WithStartRow;
use urionz\Excel\Tests\Data\Stubs\Database\User;

class WithLimitTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadLaravelMigrations(['--database' => 'testing']);
    }

    /**
     * @test
     */
    public function can_import_a_limited_section_of_rows_to_model_with_different_start_row()
    {
        $import = new class implements ToModel, WithStartRow, WithLimit {
            use Importable;

            /**
             * @param array $row
             *
             * @return Model
             */
            public function model(array $row): Model
            {
                return new User([
                    'name'     => $row[0],
                    'email'    => $row[1],
                    'password' => 'secret',
                ]);
            }

            /**
             * @return int
             */
            public function startRow(): int
            {
                return 5;
            }

            /**
             * @return int
             */
            public function limit(): int
            {
                return 1;
            }
        };

        $import->import('import-users-with-different-heading-row.xlsx');

        $this->assertDatabaseHas('users', [
            'name'  => 'Patrick Brouwers',
            'email' => 'patrick@urionz.nl',
        ]);

        $this->assertDatabaseMissing('users', [
            'name'  => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
        ]);
    }

    /**
     * @test
     */
    public function can_import_to_array_with_limit()
    {
        $import = new class implements ToArray, WithLimit {
            use Importable;

            /**
             * @param array $array
             */
            public function array(array $array)
            {
                Assert::assertEquals([
                    [
                        'Patrick Brouwers',
                        'patrick@urionz.nl',
                    ],
                ], $array);
            }

            /**
             * @return int
             */
            public function limit(): int
            {
                return 1;
            }
        };

        $import->import('import-users.xlsx');
    }
}
