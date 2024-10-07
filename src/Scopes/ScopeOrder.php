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
            if(str($column)->contains('.')) {
                return self::sortByRelation($builder, $column, $direction);
            }

            $method = $direction === 'asc' ? 'orderBy' : 'orderByDesc';
            
            $table = $builder->getModel()?->getTable();
                        
            $builder->{$method}("$table.$column", $scope);
        }

        return $builder;
    }

    /**
     * @param Builder|QueryBuilder $builder
     * @param string|null $key
     * @param string $direction
     * @return Builder|QueryBuilder
     */
    private static function sortByRelation(Builder|QueryBuilder $builder, ?string $key, string $direction = 'asc'): Builder|QueryBuilder
    {
        $relation = str($key)->beforeLast('.')->value();

        $table = $builder->getModel()?->$relation()?->getModel()?->getTable() ?? null;
        $column = str($key)->afterLast('.')->value();

        $method = $direction === 'asc' ? 'orderBy' : 'orderByDesc';

        return $builder->joinRelation("$relation")
            ->addSelect("$table.$column as {$relation}_{$column}")
            ->{$method}("{$relation}_{$column}");
    }
}
