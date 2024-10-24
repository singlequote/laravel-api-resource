<?php

namespace SingleQuote\LaravelApiResource\Service;

use SingleQuote\LaravelApiResource\Infra\ApiModel;
use SingleQuote\LaravelApiResource\Rules\MixedRule;
use SingleQuote\LaravelApiResource\Rules\OrderByRule;

use function config;

class ApiRequestService
{
    /**
     * @param string $model
     * @return array
     */
    public static function defaults(string $model): array
    {
        $fillables = ApiModel::getFillable($model);
        $relations = ApiModel::getRelations($model);

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
            'whereNull' => 'nullable|array',
            'whereNull.*' => 'required|string|in:'.$fillables,
            // Set whereNotNull
            'whereNotNull' => 'nullable|array',
            'whereNotNull.*' => 'required|string|in:'.$fillables,
            // Set Has
            'has' => 'nullable',
            'has.*' => 'required',
            // Set doesntHave
            'doesntHave' => 'nullable|array',
            'doesntHave.*' => 'required|string|in:'.$relations,
            // Set Where Relation
            'whereRelation' => 'nullable|array',
            'whereRelation.*' => 'required|array',
            'whereRelation.*.*' => ['required', new MixedRule()],
            // Set With relations
            'with' => 'nullable|array',
            'with.*' => 'required',
            // Set With count
            'withCount' => 'nullable|array',
            'withCount.*' => 'required|string|in:'.$relations,
            // Set select on columns
            'select' => 'nullable|array',
            'select.*' => 'required|string|in:'.$fillables,
            // Set the order
            'orderBy' => ['nullable', 'string', new OrderByRule($model)],
            'orderByDesc' => ['nullable', 'string', new OrderByRule($model)],
            // Trashed
            'withTrashed' => ['nullable', 'boolean'],
            'onlyTrashed' => ['nullable', 'boolean'],
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
