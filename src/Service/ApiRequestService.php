<?php

namespace SingleQuote\LaravelApiResource\Service;

use SingleQuote\LaravelApiResource\Infra\ApiModel;
use SingleQuote\LaravelApiResource\Rules\MixedRule;

use function config;

class ApiRequestService
{
    /**
     * @param string $model
     * @return array
     */
    public static function defaults(string $model): array
    {
        return [
            // Set limit per page
            'limit' => 'nullable|int|max:'.config('laravel-api-resource.api.limit', 1000).'|min:1',
            // Search on results
            'search' => 'nullable|array',
            'search.fields' => 'nullable|array',
            'search.query' => 'nullable|string|min:2|max:191',
            // Set where
            'where' => 'nullable|array',
            'where.*' => ['required', new MixedRule()],
            // Set whereIn
            'whereIn' => 'nullable|array',
            'whereIn.*' => 'required|array',
            // Set whereNotIn
            'whereNotIn' => 'nullable|array',
            'whereNotIn.*' => 'required|array',
            // Set whereNotNull
            'whereNotNull' => 'nullable|string',
            // Set Has
            'has' => 'nullable|array',
            'has.*' => 'required|string|in:'.ApiModel::getRelations($model),
            // Set doesntHave
            'doesntHave' => 'nullable|array',
            'doesntHave.*' => 'required|string|in:'.ApiModel::getRelations($model),
            // Set Where Relation
            'whereRelation' => 'nullable|array',
            'whereRelation.*' => 'required|array',
            'whereRelation.*.*' => ['required', new MixedRule()],
            // Set With relations
            'with' => 'nullable|array',
            'with.*' => 'required|string|in:'.ApiModel::getRelations($model),
            // Set select on columns
            'select' => 'nullable|array',
            'select.*' => 'required|string|in:'.ApiModel::getFillable($model),
            // Set the order
            'orderBy' => 'nullable|string|in:updated_at,created_at,'.ApiModel::getFillable($model),
            'orderByDesc' => 'nullable|string|in:updated_at,created_at,'.ApiModel::getFillable($model),
        ];
    }

    /**
     * @param string $model
     * @return array
     */
    public static function attributes(string $model): array
    {
        return [

        ];
    }
}
