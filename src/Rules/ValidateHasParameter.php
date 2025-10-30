<?php

namespace SingleQuote\LaravelApiResource\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates the 'has' parameter, which can be either a simple list
 * of relation values or an associative array of relation keys.
 */
class ValidateHasParameter implements ValidationRule
{
    protected array $allowedRelations;

    /**
     * Create a new rule instance.
     *
     * @param string $relationsString A comma-separated string of allowed relations.
     */
    public function __construct(protected readonly string $relationsString)
    {
        $this->allowedRelations = array_filter(explode(',', $this->relationsString));
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            return; // Let Laravel's 'array' rule handle this
        }

        if (empty($this->allowedRelations)) {
            return; // Nothing to validate against
        }

        if (array_is_list($value)) {
            // Scenario 1: Simple list, e.g., has[0]=posts
            // We must validate the *values* of the array.
            $invalidValues = [];
            foreach ($value as $relationName) {
                if (!is_string($relationName) || !in_array($relationName, $this->allowedRelations)) {
                    $invalidValues[] = is_string($relationName) ? $relationName : '(non-string value)';
                }
            }

            if (!empty($invalidValues)) {
                $fail("The :attribute contains invalid relations: [" . implode(', ', $invalidValues) . "].");
            }
        } else {
            // Scenario 2: Associative array, e.g., has[roles][whereIn]=...
            // We must validate the *keys* of the array.
            $inputKeys = array_keys($value);
            $invalidKeys = array_diff($inputKeys, $this->allowedRelations);

            if (!empty($invalidKeys)) {
                $fail("The :attribute contains invalid relation keys: [" . implode(', ', $invalidKeys) . "].");
            }
        }
    }
}
