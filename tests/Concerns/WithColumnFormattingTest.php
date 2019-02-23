<?php

namespace urionz\Excel\Tests\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use urionz\Excel\Tests\TestCase;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use urionz\Excel\Concerns\Exportable;
use urionz\Excel\Concerns\WithMapping;
use urionz\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use urionz\Excel\Concerns\WithColumnFormatting;

class WithColumnFormattingTest extends TestCase
{
    /**
     * @test
     */
    public function can_export_with_heading()
    {
        $export = new class() implements FromCollection, WithMapping, WithColumnFormatting {
            use Exportable;

            /**
             * @return Collection
             */
            public function collection()
            {
                return collect([
                    [Carbon::createFromDate(2018, 3, 6)],
                    [Carbon::createFromDate(2018, 3, 7)],
                    [Carbon::createFromDate(2018, 3, 8)],
                ]);
            }

            /**
             * @param mixed $row
             *
             * @return array
             */
            public function map($row): array
            {
                return [
                    Date::dateTimeToExcel($row[0]),
                ];
            }

            /**
             * @return array
             */
            public function columnFormats(): array
            {
                return [
                    'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
                ];
            }
        };

        $response = $export->store('with-column-formatting-store.xlsx');

        $this->assertTrue($response);

        $actual = $this->readAsArray(__DIR__ . '/../Data/Disks/Local/with-column-formatting-store.xlsx', 'Xlsx');

        $expected = [
            ['06/03/18'],
            ['07/03/18'],
            ['08/03/18'],
        ];

        $this->assertEquals($expected, $actual);
    }
}
