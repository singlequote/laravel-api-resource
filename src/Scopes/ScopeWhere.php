<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use SingleQuote\LaravelApiResource\Infra\ApiModel;
use SingleQuote\LaravelApiResource\Infra\Extract;

use function str;

/**
 * Description of ScopeWhere
 *
 * @author wim_p
 */
class ScopeWhere
{
    /**
     * @param Builder|QueryBuilder $builder
     * @param array $validated
     * @param string $boolean
     * @return Builder|QueryBuilder
     */
    public static function handle(Builder|QueryBuilder $builder, array $validated, string $boolean = 'and'): Builder|QueryBuilder
    {
        foreach ($validated ?? [] as $column => $scope) {

            if (is_integer($column)) {
                $builder = self::handle($builder, $scope, $boolean);
                continue;
            }

            // Grouped conditions: the $or / $and sentinel keys wrap their
            // sub-conditions in a nested where. The group attaches to the
            // surrounding query with the current boolean; inside the group the
            // sub-conditions are combined with the group's own boolean. Groups
            // may be nested (e.g. $or inside $and).
            if ($column === '$or' || $column === '$and') {
                $groupBoolean = $column === '$or' ? 'or' : 'and';

                $builder->where(function ($query) use ($scope, $groupBoolean) {
                    self::handle($query, (array) $scope, $groupBoolean);
                }, null, null, $boolean);

                continue;
            }

            [$operator, $value] = Extract::operatorAndValue($scope);

            if (str($column)->contains('.') && !self::isJsonColumn($builder, $column)) {
                $builder = self::handleRelation($builder, $boolean, $column, $scope);
                continue;
            } elseif (str($column)->contains('.') && self::isJsonColumn($builder, $column)) {
                $column = "{$builder->getModel()->getTable()}.".str($column)->replace('.', '->')->value();
            } else {
                $column = "{$builder->getModel()->getTable()}.$column";
            }

            // Respect the surrounding boolean for null checks too, so null
            // conditions inside an $or group OR correctly instead of always AND.
            if ($value === 'null' && $operator === '=') {
                $builder->whereNull($column, $boolean);
            } elseif ($value === 'null' && $operator === '!=') {
                $builder->whereNotNull($column, $boolean);
            } else {
                $builder->where($column, $operator, $value, $boolean);
            }
        }

        return $builder;
    }

    /**
     * @param Builder|QueryBuilder $builder
     * @param string $boolean
     * @param string $column
     * @param array $scope
     * @return Builder|QueryBuilder
     */
    public static function handleRelation(Builder|QueryBuilder $builder, string $boolean, string $column, array $scope): Builder|QueryBuilder
    {
        $localColumn = str($column)->before('.')->value();
        $foreignColumn = str($column)->after('.')->value();

        return ScopeWhereRelation::handle($builder, [
            $localColumn => [
                $foreignColumn => $scope,
            ]
        ], $boolean);
    }

    /**
     * @param Builder|QueryBuilder $builder
     * @param string $column
     * @return bool
     */
    public static function isJsonColumn(Builder|QueryBuilder $builder, string $column): bool
    {
        $fillables = ApiModel::fillable($builder->getModel());

        return $fillables->contains(str($column)->before('.')->value());
    }
}
