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
class ScopeWhereJsonContains
{
    /**
     * @param Builder|QueryBuilder $builder
     * @param array $validated
     * @param string $type
     * @return Builder|QueryBuilder
     */
    public static function handle(Builder|QueryBuilder $builder, array $validated, string $type = 'whereJsonContains'): Builder|QueryBuilder
    {
        foreach ($validated ?? [] as $column => $scope) {

            if (!self::isJsonColumn($builder, $column)) {
                continue;
            }

            if (is_integer($column)) {
                $builder = self::handle($builder, $scope, $type);
                continue;
            }

            [$operator, $value] = Extract::operatorAndValue($scope);

            $column = "{$builder->getModel()->getTable()}.".str($column)->replace('.', '->')->value();

            $builder->{$type}($column, $value);
        }

        return $builder;
    }

    /**
     * @param Builder|QueryBuilder $builder
     * @param string $column
     * @return bool
     */
    public static function isJsonColumn(Builder|QueryBuilder $builder, string $column): bool
    {
        $fillables = ApiModel::fillable($builder->getModel());

        return $fillables->contains(str($column)->replace('.', '->')->before('->')->value());
    }
}
