<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use SingleQuote\LaravelApiResource\Infra\Extract;

use function str;

/**
 * Description of ScopeWhere
 *
 * @author wim_p
 */
class ScopeWhere
{
    /**
     * @param Builder|QueryBuilder $builder
     * @param array $validated
     * @param string $boolean
     * @return Builder|QueryBuilder
     */
    public static function handle(Builder|QueryBuilder $builder, array $validated, string $boolean = 'and'): Builder|QueryBuilder
    {
        foreach ($validated ?? [] as $column => $scope) {

            if (is_integer($column)) {
                $builder = self::handle($builder, $scope, $boolean);
                continue;
            }

            [$operator, $value] = Extract::operatorAndValue($scope);

            if (str($column)->contains('.')) {
                $builder = self::handleRelation($builder, $boolean, $column, $scope);
                continue;
            } else {
                $column = "{$builder->getModel()->getTable()}.$column";
            }

            if ($value === 'null' && $operator === '=') {
                $builder->whereNull($column);
            } elseif ($value === 'null' && $operator === '!=') {
                $builder->whereNotNull($column);
            } else {
                $builder->where($column, $operator, $value, $boolean);
            }
        }

        return $builder;
    }

    /**
     * @param Builder|QueryBuilder $builder
     * @param string $boolean
     * @param string $column
     * @param array $scope
     * @return Builder|QueryBuilder
     */
    public static function handleRelation(Builder|QueryBuilder $builder, string $boolean, string $column, array $scope): Builder|QueryBuilder
    {
        $localColumn = str($column)->before('.')->value();
        $foreignColumn = str($column)->after('.')->value();

        return ScopeWhereRelation::handle($builder, [
            $localColumn => [
                $foreignColumn => $scope,
            ]
        ], $boolean);
    }
}
