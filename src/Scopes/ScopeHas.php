<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Description of ScopeWhere
 *
 * @author wim_p
 */
class ScopeHas
{
    /**
     * @param Builder|QueryBuilder $builder
     * @param array $validated
     * @return Builder|QueryBuilder
     */
    public static function handle(Builder|QueryBuilder $builder, array $validated): Builder|QueryBuilder
    {
        foreach ($validated ?? [] as $scope => $closure) {

            if(is_int($scope)) {
                $builder->has($closure);
            } else {
                $builder->whereHas($scope, $closure);
            }
        }

        return $builder;
    }

}
