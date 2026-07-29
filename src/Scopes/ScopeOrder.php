<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Throwable;

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
            if (str($column)->contains('.')) {
                return self::sortByRelation($builder, $column, $direction);
            }

            $method = $direction === 'asc' ? 'orderBy' : 'orderByDesc';

            $builder->{$method}($column);
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
        $relationName = str($key)->beforeLast('.')->value();
        $column = str($key)->afterLast('.')->value();

        try {
            $relation = $builder->getModel()->$relationName();
        } catch (Throwable $e) {
            return $builder;
        }

        // MorphTo has no single related table, so ordering by its column is
        // undefined. Skip rather than produce an invalid/ambiguous join.
        if ($relation instanceof MorphTo) {
            return $builder;
        }

        // To-many relations (HasMany/MorphMany) fan out when joined, producing
        // duplicate parent rows and breaking pagination. Order by a correlated
        // scalar subquery instead: no join, no duplicates, cross-database safe,
        // and rows without a related record are kept (NULL ordering).
        if ($sub = self::relationOrderSubquery($relation, $column, $direction)) {
            return $builder->orderBy($sub, $direction);
        }

        // Everything else (to-one, and pivot/through to-many we cannot correlate
        // cleanly): LEFT join so rows without a related record are not silently
        // dropped — the previous inner join removed them. Pivot to-many may still
        // duplicate parents; that case is documented as a known limitation.
        try {
            $table = $relation->getModel()->getTable();

            return $builder->leftJoinRelation($relationName)
                ->addSelect("$table.$column as {$relationName}_{$column}")
                ->orderBy("{$relationName}_{$column}", $direction);
        } catch (Throwable $e) {
            return $builder;
        }
    }

    /**
     * Build a correlated scalar subquery to order by a to-many relation column
     * without joining (which would fan out rows). Returns null for relation
     * types that need the join fallback.
     *
     * @param Relation $relation
     * @param string $column
     * @param string $direction
     * @return Builder|null
     */
    private static function relationOrderSubquery(Relation $relation, string $column, string $direction): ?Builder
    {
        // Only HasMany/MorphMany fan out and expose the keys we need to correlate.
        // (MorphMany does not extend HasMany — both extend HasOneOrMany — so we
        // must check for each explicitly.)
        if (! $relation instanceof HasMany && ! $relation instanceof MorphMany) {
            return null;
        }

        $related = $relation->getModel();

        // Self-referential relations share a table name, so the correlation would
        // be ambiguous. Fall back to the join path.
        if ($related->getTable() === $relation->getParent()->getTable()) {
            return null;
        }

        $sub = $related->newQuery()
            ->select($column)
            ->whereColumn($relation->getQualifiedForeignKeyName(), $relation->getQualifiedParentKeyName());

        if ($relation instanceof MorphMany) {
            $sub->where($relation->getQualifiedMorphType(), $relation->getMorphClass());
        }

        return $sub->orderBy($column, $direction)->limit(1);
    }
}
