<?php

namespace SingleQuote\LaravelApiResource\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MixedRule implements ValidationRule
{
    /**
     * @var array
     */
    protected array $supportedAttributes = [
        'gt',
        'gte',
        'lt',
        'lte',
        'in',
        'eq',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value) && !is_string($value)) {
            $fail('The :attribute must be either a string or array.');
        }

        if(is_array($value) && !in_array(array_key_first($value), $this->supportedAttributes)) {
            $fail('The :attribute is invalid.');
        }

        if(is_string($value) && strlen($value) > 191) {
            $fail('The :attribute is invalid.');
        }
    }
}
