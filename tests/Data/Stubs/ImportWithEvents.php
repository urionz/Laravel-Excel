<?php

namespace urionz\Excel\Tests\Data\Stubs;

use urionz\Excel\Events\AfterSheet;
use urionz\Excel\Events\AfterImport;
use urionz\Excel\Events\BeforeSheet;
use urionz\Excel\Concerns\Importable;
use urionz\Excel\Concerns\WithEvents;
use urionz\Excel\Events\BeforeImport;

class ImportWithEvents implements WithEvents
{
    use Importable;

    /**
     * @var callable
     */
    public $beforeImport;

    /**
     * @var callable
     */
    public $beforeSheet;

    /**
     * @var callable
     */
    public $afterSheet;

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            BeforeImport::class => $this->beforeImport ?? function () {
            },
            AfterImport::class => $this->afterImport ?? function () {
            },
            BeforeSheet::class => $this->beforeSheet ?? function () {
            },
            AfterSheet::class => $this->afterSheet ?? function () {
            },
        ];
    }
}
