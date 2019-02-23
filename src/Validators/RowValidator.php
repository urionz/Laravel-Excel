<?php

namespace urionz\Excel\Validators;

use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\Factory;
use urionz\Excel\Concerns\SkipsOnFailure;
use urionz\Excel\Concerns\WithValidation;
use urionz\Excel\Exceptions\RowSkippedException;
use Illuminate\Validation\ValidationException as IlluminateValidationException;

class RowValidator
{
    /**
     * @var Factory
     */
    private $validator;

    /**
     * @param Factory $validator
     */
    public function __construct(Factory $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param array          $rows
     * @param WithValidation $import
     *
     * @throws ValidationException
     * @throws RowSkippedException
     */
    public function validate(array $rows, WithValidation $import)
    {
        $rules      = $this->rules($import);
        $messages   = $this->messages($import);
        $attributes = $this->attributes($import);

        try {
            $this->validator->make($rows, $rules, $messages, $attributes)->validate();
        } catch (IlluminateValidationException $e) {
            $failures = [];
            foreach ($e->errors() as $attribute => $messages) {
                $row           = strtok($attribute, '.');
                $attributeName = strtok('');
                $attributeName = $attributes['*.' . $attributeName] ?? $attributeName;

                $failures[] = new Failure(
                    $row,
                    $attributeName,
                    str_replace($attribute, $attributeName, $messages)
                );
            }

            if ($import instanceof SkipsOnFailure) {
                $import->onFailure(...$failures);
                throw new RowSkippedException(...$failures);
            }

            throw new ValidationException(
                $e,
                $failures
            );
        }
    }

    /**
     * @param WithValidation $import
     *
     * @return array
     */
    private function messages(WithValidation $import): array
    {
        return method_exists($import, 'customValidationMessages')
            ? $this->formatKey($import->customValidationMessages())
            : [];
    }

    /**
     * @param WithValidation $import
     *
     * @return array
     */
    private function attributes(WithValidation $import): array
    {
        return method_exists($import, 'customValidationAttributes')
            ? $this->formatKey($import->customValidationAttributes())
            : [];
    }

    /**
     * @param WithValidation $import
     *
     * @return array
     */
    private function rules(WithValidation $import): array
    {
        return $this->formatKey($import->rules());
    }

    /**
     * @param array $elements
     *
     * @return array
     */
    private function formatKey(array $elements): array
    {
        return collect($elements)->mapWithKeys(function ($rule, $attribute) {
            $attribute = Str::startsWith($attribute, '*.') ? $attribute : '*.' . $attribute;

            return [$attribute => $this->formatRule($rule)];
        })->all();
    }

    /**
     * @param string|array $rules
     *
     * @return string|array
     */
    private function formatRule($rules)
    {
        if (is_array($rules)) {
            foreach ($rules as $rule) {
                $formatted[] = $this->formatRule($rule);
            }

            return $formatted ?? [];
        }

        if (Str::contains($rules, 'required_if') && preg_match('/(.*):(.*),(.*)/', $rules, $matches)) {
            $column = Str::startsWith($matches[2], '*.') ? $matches[2] : '*.' . $matches[2];

            return $matches[1] . ':' . $column . ',' . $matches[3];
        }

        return $rules;
    }
}
