<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class ScopeWhereIn
{
    /**
     * @param Builder|QueryBuilder $builder
     * @param array $validated
     * @return Builder|QueryBuilder
     */
    public static function handle(Builder|QueryBuilder $builder, array $validated): Builder|QueryBuilder
    {
        foreach ($validated ?? [] as $column => $scope) {

            if(str($column)->contains('.')) {
                $builder = self::handleRelation($builder, $column, $scope);
                continue;
            }

            $builder->whereIn($column, $scope);
        }

        return $builder;
    }

    /**
     * @param Builder|QueryBuilder $builder
     * @param string $column
     * @param array $scope
     * @return Builder|QueryBuilder
     */
    public static function handleRelation(Builder|QueryBuilder $builder, string $column, array $scope): Builder|QueryBuilder
    {
        $localColumn = str($column)->before('.')->value();
        $foreignColumn = str($column)->after('.')->value();

        return ScopeHas::handle($builder, [
            $localColumn => function (Builder|QueryBuilder $query) use ($foreignColumn, $scope) {
                return $query->whereIn($foreignColumn, $scope);
            }
        ]);
    }

}
