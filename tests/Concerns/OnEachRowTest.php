<?php

namespace urionz\Excel\Tests\Concerns;

use urionz\Excel\Row;
use PHPUnit\Framework\Assert;
use urionz\Excel\Tests\TestCase;
use urionz\Excel\Concerns\OnEachRow;
use urionz\Excel\Concerns\Importable;

class OnEachRowTest extends TestCase
{
    /**
     * @test
     */
    public function can_import_each_row_individually()
    {
        $import = new class implements OnEachRow {
            use Importable;

            public $called = 0;

            /**
             * @param Row $row
             */
            public function onRow(Row $row)
            {
                foreach ($row->getCellIterator() as $cell) {
                    Assert::assertEquals('test', $cell->getValue());
                }

                Assert::assertEquals([
                    'test', 'test',
                ], $row->toArray());

                $this->called++;
            }
        };

        $import->import('import.xlsx');

        $this->assertEquals(2, $import->called);
    }
}
