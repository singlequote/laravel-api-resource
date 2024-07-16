<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use SingleQuote\LaravelApiResource\Infra\Extract;

/**
 * Description of ScopeWhere
 *
 * @author wim_p
 */
class ScopeWhereRelation
{
    /**
     * @param Builder|QueryBuilder $builder
     * @param array $validated
     * @return Builder|QueryBuilder
     */
    public static function handle(Builder|QueryBuilder $builder, array $validated, string $boolean = 'and'): Builder|QueryBuilder
    {
        foreach ($validated ?? [] as $relation => $scope) {
            foreach ($scope as $column => $scopeValue) {
                self::build($builder, $relation, $column, $scopeValue, $boolean);
            }
        }

        return $builder;
    }

    /**
     * @param Builder|QueryBuilder $builder
     * @param string $relation
     * @param string $column
     * @param string|array $scopeValue
     * @return Builder|QueryBuilder
     */
    private static function build(Builder|QueryBuilder $builder, string $relation, string $column, string|array$scopeValue, string $boolean): Builder|QueryBuilder
    {
        [$operator, $value] = Extract::operatorAndValue($scopeValue);

        $method = $boolean === 'and' ? 'whereRelation' : 'orWhereRelation';

        if ($value === null) {
            $builder->{$method}($relation, $column, $value);
        }

        if ($value !== null) {
            $builder->{$method}($relation, $column, $operator, $value);
        }

        return $builder;
    }
}
