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
        $fillable = ApiModel::fillable($builder->getModel());
        $fields = $searchable['fields'][0] === '*' ? $fillable : $searchable['fields'];
        $allowed = [...$fillable, 'created_at', 'updated_at', 'deleted_at'];
        $lower = config('laravel-api-resource.search.lower', true);

        foreach ($fields ?? [] as $column) {

            if (str($column)->contains('|')) {
                $builder = self::searchRelation($builder, str($column)->before('|'), str($column)->after('|'), str($searchable['query'])->lower());

                continue;
            }

            if (!in_array(str($column)->before('->')->value(), $allowed)) {
                continue;
            }

            $search = str($searchable['query'])->lower()->replace(' + ', '+')->explode('+');

            foreach ($search as $searchKey) {

                if (! str($searchKey)->contains('%')) {
                    $searchKey = "%{$searchKey}%";
                }

                $builder = $lower
                    ? $builder->orWhereRaw("LOWER($column) LIKE ?", [$searchKey])
                    : $builder->orWhereRaw("$column LIKE ?", [$searchKey]);
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
        $lower = config('laravel-api-resource.search.lower', true);

        return $builder->orWhereHas($relation, function (Builder $builder) use ($column, $search, $lower) {
            $lower
                ? $builder->whereRaw("LOWER($column) LIKE ?", ["%{$search}%"])
                : $builder->whereRaw("$column LIKE ?", ["%{$search}%"]);
        });
    }
}
