<?php

namespace SingleQuote\LaravelApiResource\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use SingleQuote\LaravelApiResource\Infra\Operator;

class MixedRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value) && !is_string($value)) {
            $fail('The :attribute must be either a string or array.');
        }

        //        if(is_array($value) && is_array($value[array_key_first($value)])) {
        //            dd($value);
        //            $fail('The :attribute operator is invalid.');
        //        }
        //
        //        if(is_array($value) && !in_array(array_key_first($value), Operator::allowed())) {
        //            $fail('The :attribute operator is invalid.');
        //        }

        if(is_string($value) && strlen($value) > 191) {
            $fail('The :attribute is invalid.');
        }
    }
}
