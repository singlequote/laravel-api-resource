<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use SingleQuote\LaravelApiResource\Infra\ReExecute;

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
            }

            if(is_string($scope) && $closure instanceof Closure) {
                $builder->whereHas($scope, $closure);
            }

            if(is_string($scope) && is_array($closure)) {
                $builder->whereHas($scope, function (Builder $query) use ($closure) {
                    return ReExecute::handle($query, $closure);
                });
            }
        }

        return $builder;
    }

}
