<?php

namespace SingleQuote\LaravelApiResource\Infra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Throwable;

use function collect;

/**
 * Description of Operator
 *
 * @author wim_p
 */
class ApiModel
{
    /**
     * In-process memo for introspection results. Keyed by "class|flag".
     *
     * Fillables/relations are structural: they only change on a code deploy,
     * which restarts Octane workers and clears this static state. Memoizing here
     * avoids repeated cache-store round-trips (file/redis) within a request and,
     * under Octane, across requests in the same worker.
     *
     * @var array<string, array>
     */
    private static array $relationsMemo = [];

    /**
     * @var array<string, Collection>
     */
    private static array $fillableMemo = [];

    /**
     * @param string|Model $modelClass
     * @param bool $withSubRelations
     * @return string
     */
    public static function getRelations(string|Model $modelClass, bool $withSubRelations = true): string
    {
        if ($modelClass instanceof Model) {
            $model = $modelClass;
        } else {
            if (!class_exists($modelClass)) {
                return '';
            }

            $model = (new $modelClass());
        }

        $apiIncluded = self::relations($model, $withSubRelations);

        return collect($apiIncluded)->unique()->implode(',');
    }

    /**
     * @param Model $model
     * @param bool $withSubRelations
     * @return array
     */
    public static function relations(Model $model, bool $withSubRelations = true): array
    {
        $memoKey = $model::class . '|' . ($withSubRelations ? '1' : '0');

        if (isset(self::$relationsMemo[$memoKey])) {
            return self::$relationsMemo[$memoKey];
        }

        $cacheKey = str($model::class)->prepend('relations-')->append('-wr-')->append($withSubRelations ? 'true' : 'false')->slug()->value();

        return self::$relationsMemo[$memoKey] = cache()->remember($cacheKey, config('laravel-api-resource.cache.relations', 3600), function () use ($model, $withSubRelations) {

            $relations = $model->definedRelations();

            $additionalRelations = $withSubRelations ? ($model->apiRelations ?? []) : [];

            foreach ($relations as $relation) {

                if (!$withSubRelations) {
                    continue;
                }

                $additionalRelations[] = $relation;

                try {
                    $relationModel = $model->$relation()->getModel();
                } catch (Throwable $ex) {
                    continue;
                }


                if (isset($relationModel->apiRelations)) {
                    $additionalRelations = [
                        ... $additionalRelations,
                        ... collect($relationModel->apiRelations)->map(fn ($r) => "$relation.$r")->toArray()
                    ];
                }
            }

            return array_merge($relations, $additionalRelations);
        });
    }

    /**
     * @param string|Model $modelClass
     * @return string
     */
    public static function getFillable(string|Model $modelClass, bool $withRelations = false): string
    {
        if ($modelClass instanceof Model) {
            $model = $modelClass;
        } else {
            $model = (new $modelClass());
        }

        return self::fillable($model, $withRelations)->implode(',');
    }

    /**
     * @param string|Model $modelClass
     * @return string
     */
    public static function fillable(Model $model, bool $withRelations = false): Collection
    {
        $memoKey = $model::class . '|' . ($withRelations ? '1' : '0');

        if (isset(self::$fillableMemo[$memoKey])) {
            return self::$fillableMemo[$memoKey];
        }

        $cacheKey = str($model::class)->prepend('fillables-')->append('-wr-')->append($withRelations ? 'true' : 'false')->slug()->value();

        return self::$fillableMemo[$memoKey] = cache()->remember($cacheKey, config('laravel-api-resource.cache.fillables', 3600), function () use ($model, $withRelations) {
            $timestamps = config('laravel-api-resource.columns.default', [
                'id',
                'created_at',
                'updated_at',
            ]);

            $fillables = [
                ... $timestamps,
                ... method_exists($model, 'bootSoftDeletes') ? ['deleted_at'] : [],
                ... $model->getFillable(),
                ... $withRelations ? self::relations($model, true) : [],
            ];

            $hidden = $model->getHidden();

            return collect($fillables)->filter(function ($fill) use ($hidden) {
                return !in_array($fill, $hidden);
            });
        });
    }
}
