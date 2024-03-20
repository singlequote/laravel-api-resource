<?php

namespace SingleQuote\LaravelApiResource\Service;

class ApiRequestService
{
    public static function defaults(string $model): array
    {
        return [
            // Set limit per page
            'limit' => 'nullable|int|max:10000|min:1',
            // Search on results
            'search' => 'nullable|array',
            'search.fields' => 'nullable|array',
            'search.query' => 'nullable|string|min:3|max:191',

            // Set where
            'where' => 'nullable|array',
            'where.*' => 'required|string',

            // Set whereIn
            'whereIn' => 'nullable|array',
            'whereIn.*' => 'required|array',

            // Set whereNotIn
            'whereNotIn' => 'nullable|array',
            'whereNotIn.*' => 'required|array',

            // Set WhereHas
            'whereHas' => 'nullable|array',
            'whereHas.*' => 'required|string|in:'.self::getRelations($model),

            // Set Where Relation
            'whereRelation' => 'nullable|array',
            'whereRelation.*' => 'required|array',

            // Set With relations
            'with' => 'nullable|array',
            'with.*' => 'required|string|in:'.self::getRelations($model),

            // Set select on columns
            'select' => 'nullable|array',
            'select.*' => 'required|string|in:'.self::getFillable($model),

            // Set the order
            'orderBy' => 'nullable|string|in:updated_at,created_at,'.self::getFillable($model),
            'orderByDesc' => 'nullable|string|in:updated_at,created_at,'.self::getFillable($model),
        ];
    }

    public static function attributes(string $model): array
    {
        return [

        ];
    }

    public static function getRelations(string $model): string
    {
        if (! class_exists($model)) {
            return '';
        }

        $relations = (new $model())->definedRelations();

        $apiIncluded = array_merge($relations, isset((new $model())->apiRelations) ? (new $model())->apiRelations : []);

        return collect($apiIncluded)->implode(',');
    }

    public static function getFillable(string $model): string
    {
        if (! class_exists($model)) {
            return '';
        }

        $fillables = [
            'id',
            ...(new $model())->getFillable(),
        ];

        $hidden = (new $model())->getHidden();

        return collect($fillables)->filter(function ($fill) use ($hidden) {
            return ! in_array($fill, $hidden);
        })->implode(',');
    }
}
