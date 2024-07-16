<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Description of ScopeWhere
 *
 * @author wim_p
 */
class ScopeSelect
{
    /**
     * @param Builder|QueryBuilder $builder
     * @param array $validated
     * @return Builder|QueryBuilder
     */
    public static function handle(Builder|QueryBuilder $builder, array $validated): Builder|QueryBuilder
    {
        if (count($validated ?? []) === 0) {
            return $builder;
        }

        $builder->select('id');

        foreach ($validated ?? [] as $column) {

            if (!in_array(str($column)->before('->')->value(), [...\SingleQuote\LaravelApiResource\Infra\ApiModel::getFillable($builder->getModel()), 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $builder->addSelect($column);
        }

        return $builder;
    }

}
