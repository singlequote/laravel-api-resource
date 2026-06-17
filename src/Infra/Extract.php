<?php

namespace SingleQuote\LaravelApiResource\Infra;

/**
 * Description of Action
 *
 * @author wim_p
 */
class Extract
{
    /**
     * @param string|array $scope
     * @return array
     */
    public static function operatorAndValue(string|array $scope): array
    {
        $operator = is_array($scope) ? Operator::toSql(array_key_first($scope)) : '=';
        $value = is_array($scope) ? $scope[array_key_first($scope)] : $scope;

        return [
            str($operator)->replace('%', '')->value(),
            self::parseValue($operator, $value ?? ''),
        ];
    }

    /**
     * @param string $operator
     * @param string $value
     * @return string
     */
    private static function parseValue(string $operator, string $value): string
    {
        if (in_array($operator, ["LIKE", "NOT LIKE"])) {
            return "%$value%";
        }

        if ($operator === "LIKE%") {
            return "$value%";
        }

        if ($operator === "%LIKE") {
            return "%$value";
        }

        return $value;
    }
}