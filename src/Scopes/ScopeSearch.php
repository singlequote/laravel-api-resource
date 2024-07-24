<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use SingleQuote\LaravelApiResource\Infra\ApiModel;

use function str;

/**
 * Description of ScopeWhere
 *
 * @author wim_p
 */
class ScopeSearch
{
    /**
     * @param Builder|QueryBuilder $builder
     * @param array $validated
     * @return Builder|QueryBuilder
     */
    public static function handle(Builder|QueryBuilder $builder, array $validated): Builder|QueryBuilder
    {
        if (count($validated) === 0) {
            return $builder;
        }

        return $builder->where(function (Builder $builder) use ($validated) {
            self::applySearch($builder, $validated);

            return $builder;
        });
    }

    /**
     * @param Builder|QueryBuilder $builder
     * @param array $searchable
     * @return Builder|QueryBuilder
     */
    private static function applySearch(Builder|QueryBuilder $builder, array $searchable): Builder|QueryBuilder
    {
        $fields = $searchable['fields'][0] === '*' ? ApiModel::fillable($builder->getModel()) : $searchable['fields'];

        foreach ($fields ?? [] as $column) {

            if (str($column)->contains('|')) {
                $builder = self::searchRelation($builder, str($column)->before('|'), str($column)->after('|'), str($searchable['query'])->lower());

                continue;
            }

            if (!in_array(str($column)->before('->')->value(), [...ApiModel::fillable($builder->getModel()), 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $search = str($searchable['query'])->lower()->explode(' ');

            foreach ($search as $searchKey) {
                $builder = $builder->orWhereRaw("LOWER($column) LIKE ?", ["%{$searchKey}%"]);
            }
        }

        return $builder;
    }

    /**
     * @param Builder|QueryBuilder $builder
     * @param string $relation
     * @param string $column
     * @param string $search
     * @return Builder|QueryBuilder
     */
    private static function searchRelation(Builder|QueryBuilder $builder, string $relation, string $column, string $search): Builder|QueryBuilder
    {
        return $builder->orWhereHas($relation, function (Builder $builder) use ($column, $search) {
            $builder->whereRaw("LOWER($column) LIKE ?", ["%{$search}%"]);
        });
    }
}
