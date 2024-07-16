<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Description of ScopeWhere
 *
 * @author wim_p
 */
class ScopeOrder
{
    /**
     * @param Builder|QueryBuilder $builder
     * @param string|null $column
     * @param string $direction
     * @return Builder|QueryBuilder
     */
    public static function handle(Builder|QueryBuilder $builder, ?string $column, string $direction = 'asc'): Builder|QueryBuilder
    {
        if ($column) {
            $method = $direction === 'asc' ? 'orderBy' : 'orderByDesc';

            $builder->{$method}($order);
        }

        return $builder;
    }
}
