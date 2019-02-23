<?php

namespace urionz\Excel\Tests\Data\Stubs;

use urionz\Excel\Writer;
use Illuminate\Support\Collection;
use urionz\Excel\Tests\TestCase;
use urionz\Excel\Concerns\WithTitle;
use urionz\Excel\Concerns\Exportable;
use urionz\Excel\Concerns\WithEvents;
use urionz\Excel\Events\BeforeWriting;
use urionz\Excel\Concerns\FromCollection;
use urionz\Excel\Concerns\ShouldAutoSize;
use urionz\Excel\Concerns\RegistersEventListeners;

class SheetWith100Rows implements FromCollection, WithTitle, ShouldAutoSize, WithEvents
{
    use Exportable, RegistersEventListeners;

    /**
     * @var string
     */
    private $title;

    /**
     * @param string $title
     */
    public function __construct(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $collection = new Collection;
        for ($i = 0; $i < 100; $i++) {
            $row = new Collection();
            for ($j = 0; $j < 5; $j++) {
                $row[] = $this->title() . '-' . $i . '-' . $j;
            }

            $collection->push($row);
        }

        return $collection;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * @param BeforeWriting $event
     */
    public static function beforeWriting(BeforeWriting $event)
    {
        TestCase::assertInstanceOf(Writer::class, $event->writer);
    }
}
