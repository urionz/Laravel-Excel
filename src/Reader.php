<?php

namespace urionz\Excel;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use urionz\Excel\Events\AfterImport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use urionz\Excel\Concerns\WithEvents;
use urionz\Excel\Events\BeforeImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use urionz\Excel\Helpers\FilePathHelper;
use urionz\Excel\Factories\ReaderFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use urionz\Excel\Concerns\MapsCsvSettings;
use urionz\Excel\Concerns\WithChunkReading;
use urionz\Excel\Concerns\SkipsUnknownSheets;
use urionz\Excel\Concerns\WithMultipleSheets;
use urionz\Excel\Concerns\WithCustomCsvSettings;
use urionz\Excel\Concerns\WithCustomValueBinder;
use urionz\Excel\Concerns\WithCalculatedFormulas;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use urionz\Excel\Exceptions\SheetNotFoundException;
use urionz\Excel\Exceptions\NoTypeDetectedException;

class Reader
{
    use DelegatedMacroable, HasEventBus, MapsCsvSettings;

    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /**
     * @var object[]
     */
    protected $sheetImports = [];

    /**
     * @var string
     */
    protected $currentFile;

    /**
     * @var FilePathHelper
     */
    protected $filePathHelper;

    /**
     * @param FilePathHelper $filePathHelper
     */
    public function __construct(FilePathHelper $filePathHelper)
    {
        $this->filePathHelper = $filePathHelper;

        $this->applyCsvSettings(config('excel.imports.csv', []));
        $this->setDefaultValueBinder();
    }

    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     * @param string|null         $readerType
     * @param string|null         $disk
     *
     * @throws Exception
     * @throws NoTypeDetectedException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Foundation\Bus\PendingDispatch|$this
     */
    public function read($import, $filePath, string $readerType = null, string $disk = null)
    {
        $reader = $this->getReader($import, $filePath, $readerType, $disk);

        if ($import instanceof WithChunkReading) {
            return app(ChunkReader::class)->read($import, $reader, $this->currentFile);
        }

        $this->beforeReading($import, $reader);

        DB::transaction(function () use ($import) {
            foreach ($this->sheetImports as $index => $sheetImport) {
                if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                    $sheet->import($sheetImport, $sheet->getStartRow($sheetImport));
                    $sheet->disconnect();
                }
            }
        });

        $this->afterReading($import);

        return $this;
    }

    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     * @param string              $readerType
     * @param string|null         $disk
     *
     * @throws Exceptions\SheetNotFoundException
     * @throws Exceptions\UnreadableFileException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws NoTypeDetectedException
     * @return array
     */
    public function toArray($import, $filePath, string $readerType, string $disk = null): array
    {
        $reader = $this->getReader($import, $filePath, $readerType, $disk);
        $this->beforeReading($import, $reader);

        $sheets = [];
        foreach ($this->sheetImports as $index => $sheetImport) {
            $calculatesFormulas = $sheetImport instanceof WithCalculatedFormulas;
            if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                $sheets[$index] = $sheet->toArray($sheetImport, $sheet->getStartRow($sheetImport), null, $calculatesFormulas);
                $sheet->disconnect();
            }
        }

        $this->afterReading($import);

        return $sheets;
    }

    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     * @param string              $readerType
     * @param string|null         $disk
     *
     * @throws Exceptions\SheetNotFoundException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws NoTypeDetectedException
     * @return Collection
     */
    public function toCollection($import, $filePath, string $readerType, string $disk = null): Collection
    {
        $reader = $this->getReader($import, $filePath, $readerType, $disk);
        $this->beforeReading($import, $reader);

        $sheets = new Collection();
        foreach ($this->sheetImports as $index => $sheetImport) {
            $calculatesFormulas = $sheetImport instanceof WithCalculatedFormulas;
            if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                $sheets->put($index, $sheet->toCollection($sheetImport, $sheet->getStartRow($sheetImport), null, $calculatesFormulas));
                $sheet->disconnect();
            }
        }

        $this->afterReading($import);

        return $sheets;
    }

    /**
     * @return object
     */
    public function getDelegate()
    {
        return $this->spreadsheet;
    }

    /**
     * @return $this
     */
    public function setDefaultValueBinder(): self
    {
        Cell::setValueBinder(new DefaultValueBinder());

        return $this;
    }

    /**
     * @param $import
     * @param $sheetImport
     * @param $index
     *
     * @throws SheetNotFoundException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @return Sheet|null
     */
    protected function getSheet($import, $sheetImport, $index)
    {
        try {
            return Sheet::make($this->spreadsheet, $index);
        } catch (SheetNotFoundException $e) {
            if ($import instanceof SkipsUnknownSheets) {
                $import->onUnknownSheet($index);

                return null;
            }

            if ($sheetImport instanceof SkipsUnknownSheets) {
                $sheetImport->onUnknownSheet($index);

                return null;
            }

            throw $e;
        }
    }

    /**
     * Garbage collect.
     */
    private function garbageCollect()
    {
        $this->setDefaultValueBinder();

        // Force garbage collecting
        unset($this->sheetImports, $this->spreadsheet);

        $this->filePathHelper->deleteTempFile($this->currentFile);
    }

    /**
     * @param object  $import
     * @param IReader $reader
     *
     * @return array
     */
    private function buildSheetImports($import, IReader $reader): array
    {
        $sheetImports = [];
        if ($import instanceof WithMultipleSheets) {
            $sheetImports = $import->sheets();

            // When only sheet names are given and the reader has
            // an option to load only the selected sheets.
            if (
                method_exists($reader, 'setLoadSheetsOnly')
                && count(array_filter(array_keys($sheetImports), 'is_numeric')) === 0
            ) {
                $reader->setLoadSheetsOnly(array_keys($sheetImports));
            }
        }

        return $sheetImports;
    }

    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     * @param string|null         $readerType
     * @param string              $disk
     *
     * @throws InvalidArgumentException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws NoTypeDetectedException
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @return IReader
     */
    private function getReader($import, $filePath, string $readerType = null, string $disk = null): IReader
    {
        $shouldQueue = $import instanceof ShouldQueue;
        if ($shouldQueue && !$import instanceof WithChunkReading) {
            throw new InvalidArgumentException('ShouldQueue is only supported in combination with WithChunkReading.');
        }

        if ($import instanceof WithEvents) {
            $this->registerListeners($import->registerEvents());
        }

        if ($import instanceof WithCustomValueBinder) {
            Cell::setValueBinder($import);
        }

        if ($import instanceof WithCustomCsvSettings) {
            $this->applyCsvSettings($import->getCsvSettings());
        }

        $this->currentFile = $this->filePathHelper->copyToTempFile($filePath, $disk, $shouldQueue);

        $reader = ReaderFactory::make($this->getReaderType($readerType));

        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(config('excel.imports.read_only', true));
        }

        if ($reader instanceof Csv) {
            $reader->setDelimiter($this->delimiter);
            $reader->setEnclosure($this->enclosure);
            $reader->setEscapeCharacter($this->escapeCharacter);
            $reader->setContiguous($this->contiguous);
            $reader->setInputEncoding($this->inputEncoding);
        }

        return $reader;
    }

    /**
     * @param object  $import
     * @param IReader $reader
     *
     * @throws Exception
     */
    private function beforeReading($import, IReader $reader)
    {
        $this->sheetImports = $this->buildSheetImports($import, $reader);

        $this->spreadsheet = $reader->load($this->filePathHelper->getTempPath($this->currentFile));

        // When no multiple sheets, use the main import object
        // for each loaded sheet in the spreadsheet
        if (!$import instanceof WithMultipleSheets) {
            $this->sheetImports = array_fill(0, $this->spreadsheet->getSheetCount(), $import);
        }

        $this->raise(new BeforeImport($this, $import));
    }

    /**
     * @param object $import
     */
    private function afterReading($import)
    {
        $this->raise(new AfterImport($this, $import));
        $this->garbageCollect();
    }

    /**
     * @param string|null $readerType
     *
     * @throws NoTypeDetectedException
     * @return string
     */
    private function getReaderType(string $readerType = null): string
    {
        if (null !== $readerType) {
            return $readerType;
        }

        try {
            return IOFactory::identify($this->filePathHelper->getTempPath($this->currentFile));
        } catch (Exception $e) {
            throw new NoTypeDetectedException(null, null, $e);
        }
    }
}
