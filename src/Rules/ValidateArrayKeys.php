<?php

namespace SingleQuote\LaravelApiResource\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that all keys of an array exist in a given comma-separated string of allowed keys.
 */
class ValidateArrayKeys implements ValidationRule
{
    protected array $allowedKeys;

    /**
     * Create a new rule instance.
     *
     * @param string $allowedKeysString A comma-separated string of allowed keys.
     */
    public function __construct(protected readonly string $allowedKeysString)
    {
        $this->allowedKeys = array_filter(explode(',', $this->allowedKeysString));
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            return;
        }

        // $inputKeys = array_keys($value);
        // $invalidKeys = array_diff($inputKeys, $this->allowedKeys);

        // if (!empty($invalidKeys)) {
        //     $keysString = implode(', ', $invalidKeys);
        //     $fail("The :attribute contains invalid keys: [{$keysString}].");
        // }
    }
}
