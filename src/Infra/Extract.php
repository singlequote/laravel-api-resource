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
            $operator,
            $operator === "LIKE" ? "%$value%" : $value,
        ];
    }
}
