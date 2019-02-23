<?php

namespace urionz\Excel\Tests\Concerns;

use Throwable;
use PHPUnit\Framework\Assert;
use urionz\Excel\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use urionz\Excel\Concerns\ToModel;
use urionz\Excel\Concerns\Importable;
use urionz\Excel\Concerns\SkipsErrors;
use urionz\Excel\Concerns\SkipsOnError;
use urionz\Excel\Tests\Data\Stubs\Database\User;

class SkipsOnErrorTest extends TestCase
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
    public function can_skip_on_error()
    {
        $import = new class implements ToModel, SkipsOnError {
            use Importable;

            public $errors = 0;

            /**
             * @param array $row
             *
             * @return Model|null
             */
            public function model(array $row)
            {
                return new User([
                    'name'     => $row[0],
                    'email'    => $row[1],
                    'password' => 'secret',
                ]);
            }

            /**
             * @param Throwable $e
             */
            public function onError(Throwable $e)
            {
                Assert::assertInstanceOf(QueryException::class, $e);
                Assert::stringContains($e->getMessage(), 'Duplicate entry \'patrick@urionz.nl\'');

                $this->errors++;
            }
        };

        $import->import('import-users-with-duplicates.xlsx');

        $this->assertEquals(1, $import->errors);

        // Shouldn't have rollbacked other imported rows.
        $this->assertDatabaseHas('users', [
            'email' => 'patrick@urionz.nl',
        ]);

        // Should have skipped inserting
        $this->assertDatabaseMissing('users', [
            'email' => 'taylor@laravel.com',
        ]);
    }

    /**
     * @test
     */
    public function can_skip_errors_and_collect_all_errors_at_the_end()
    {
        $import = new class implements ToModel, SkipsOnError {
            use Importable, SkipsErrors;

            /**
             * @param array $row
             *
             * @return Model|null
             */
            public function model(array $row)
            {
                return new User([
                    'name'     => $row[0],
                    'email'    => $row[1],
                    'password' => 'secret',
                ]);
            }
        };

        $import->import('import-users-with-duplicates.xlsx');

        $this->assertCount(1, $import->errors());

        /** @var Throwable $e */
        $e = $import->errors()->first();

        $this->assertInstanceOf(QueryException::class, $e);
        $this->stringContains($e->getMessage(), 'Duplicate entry \'patrick@urionz.nl\'');

        // Shouldn't have rollbacked other imported rows.
        $this->assertDatabaseHas('users', [
            'email' => 'patrick@urionz.nl',
        ]);

        // Should have skipped inserting
        $this->assertDatabaseMissing('users', [
            'email' => 'taylor@laravel.com',
        ]);
    }
}
