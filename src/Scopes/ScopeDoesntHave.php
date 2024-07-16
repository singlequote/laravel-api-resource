<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Description of ScopeWhere
 *
 * @author wim_p
 */
class ScopeDoesntHave
{
    /**
     * @param Builder|QueryBuilder $builder
     * @param array $validated
     * @return Builder|QueryBuilder
     */
    public static function handle(Builder|QueryBuilder $builder, array $validated): Builder|QueryBuilder
    {
        foreach ($validated ?? [] as $scope) {
            $builder->doesntHave($scope);
        }

        return $builder;
    }

}
