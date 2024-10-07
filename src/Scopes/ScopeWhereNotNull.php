<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class ScopeWhereNotNull
{
    /**
     * @param Builder|QueryBuilder $builder
     * @param array $validated
     * @return Builder|QueryBuilder
     */
    public static function handle(Builder|QueryBuilder $builder, array $validated): Builder|QueryBuilder
    {
        $table = $builder->getModel()?->getTable();

        foreach ($validated ?? [] as $scope) {
            $builder->whereNotNull("$table.$column", $scope);
        }

        return $builder;
    }

}
