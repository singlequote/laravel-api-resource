<?php

namespace SingleQuote\LaravelApiResource\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use SingleQuote\LaravelApiResource\Infra\ApiModel;

use function str;

class OrderByRule implements ValidationRule
{
    public function __construct(protected string $model)
    {

    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $relations = ApiModel::relations(new $this->model());
        $fillable = ApiModel::fillable(new $this->model());

        if(str($value)->contains('.') && str($value)->substrCount('.') > 1) {
            $fail('Only 1 nested relation is allowed on :attribute.');
        }

        if(str($value)->contains('.') && ! in_array(str($value)->before('.')->value(), $relations)) {
            $fail('Relation does not exists on :attribute.');
        }

        if(! str($value)->contains('.') && ! in_array($value, [... $fillable, 'id'])) {
            $fail('Column does not exists on :attribute.');
        }
    }
}
