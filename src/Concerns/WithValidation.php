<?php

namespace urionz\Excel\Concerns;

interface WithValidation
{
    /**
     * @return array
     */
    public function rules(): array;
}
