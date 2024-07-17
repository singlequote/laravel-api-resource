<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use SingleQuote\LaravelApiResource\Infra\ApiModel;

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
        $table = $builder->getModel()->getTable();
        $timestamps = self::getTimestamps($builder, $table);

        if (count($validated ?? []) === 0) {
            return self::filterDefaultSelect($builder, $table, $timestamps);
        }

        $builder->select("$table.id");

        foreach ($validated ?? [] as $column) {
            $builder->addSelect("$table.$column");
        }

        return $builder;
    }

    /**
     * @param Builder|QueryBuilder $builder
     * @param string $table
     * @param array $timestamps
     * @return Builder|QueryBuilder
     */
    private static function filterDefaultSelect(Builder|QueryBuilder $builder, string $table, array $timestamps): Builder|QueryBuilder
    {
        $filtered = ApiModel::fillable($builder->getModel())->map(function (string $fillable) use ($table) {
            return "$table.$fillable";
        })
        ->merge($timestamps)
        ->toArray();

        return $builder->select($filtered);
    }

    /**
     * @param Builder|QueryBuilder $builder
     * @param string $table
     * @return array
     */
    private static function getTimestamps(Builder|QueryBuilder $builder, string $table): array
    {
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses($builder->getModel()::class), true);

        return [
            "$table.created_at",
            "$table.updated_at",
            ... $usesSoftDeletes ? ["$table.deleted_at"] : [],
        ];
    }

}
