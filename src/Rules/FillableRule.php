<?php

namespace SingleQuote\LaravelApiResource\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use function str;

class FillableRule implements ValidationRule
{
    public function __construct(protected readonly string $fillables)
    {
        //
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fillables = explode(',', $this->fillables);

        $key = str($value)->before('->')->value();

        if (!in_array($key, $fillables)) {
            $fail("The selected :attribute '$value' is not a fillable field.");
        }
    }
}
