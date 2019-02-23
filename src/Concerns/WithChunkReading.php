<?php

namespace urionz\Excel\Concerns;

interface WithChunkReading
{
    /**
     * @return int
     */
    public function chunkSize(): int;
}
