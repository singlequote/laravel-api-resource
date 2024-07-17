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
            $builder->whereIn($column, $scope);
        }

        return $builder;
    }

}
