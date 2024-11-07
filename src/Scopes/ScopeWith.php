<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use SingleQuote\LaravelApiResource\Infra\ReExecute;

/**
 * Description of ScopeWhere
 *
 * @author wim_p
 */
class ScopeWith
{
    /**
     * @param Builder|QueryBuilder $builder
     * @param FormRequest|Request $request
     * @return Builder|QueryBuilder
     */
    public static function handle(Builder|QueryBuilder $builder, FormRequest|Request $request): Builder|QueryBuilder
    {
        $relations = self::getRelations($request);

        if (count($relations) === 0) {
            return $builder;
        }

        foreach ($relations as $relation => $data) {
            if (is_string($relation) && is_array($data)) {
                self::parseRelationBuilder($builder, $relation, $data);
                continue;
            }

            if (is_string($relation) && (bool) $data === true) {
                $builder->with($relation);
                continue;
            }

            $builder->with($data);
        }

        return $builder;
    }

    /**
     * @param Model $model
     * @param FormRequest|Request $request
     * @return Model
     */
    public static function handleModel(Model $model, FormRequest|Request $request): Model
    {
        $relations = self::getRelations($request);

        if (count($relations) === 0) {
            return $model;
        }

        foreach ($relations as $relation => $data) {
            if (is_string($relation) && is_array($data)) {
                self::parseRelationBuilderModel($model, $relation, $data);
                continue;
            }

            if (is_string($relation) && (bool) $data === true) {
                $model->load($relation);
                continue;
            }

            $model->load($data);
        }

        return $model;
    }

    /**
     * @param Builder $builder
     * @param string $relation
     * @param array $data
     * @return Builder
     */
    public static function parseRelationBuilder(Builder &$builder, string $relation, array $data): Builder
    {
        return $builder->with([$relation => function (Relation $query) use ($data) {
            return ReExecute::handle($query, $data);
        }]);
    }

    /**
     * @param Model $model
     * @param string $relation
     * @param array $data
     * @return Model
     */
    public static function parseRelationBuilderModel(Model &$model, string $relation, array $data): Model
    {
        return $model->load([$relation => function (Relation $query) use ($data) {
            return ReExecute::handle($query, $data);
        }]);
    }

    /**
     * @param FormRequest|Request $request
     * @return array
     */
    public static function getRelations(FormRequest|Request $request): array
    {
        if ($request instanceof FormRequest) {
            return $request->validated('with', []);
        }

        return $request->get('with', []);
    }
}
