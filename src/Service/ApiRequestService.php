<?php

namespace SingleQuote\LaravelApiResource\Service;

use SingleQuote\LaravelApiResource\Infra\ApiModel;
use SingleQuote\LaravelApiResource\Rules\FillableRule;
use SingleQuote\LaravelApiResource\Rules\MixedRule;
use SingleQuote\LaravelApiResource\Rules\OrderByRule;
use SingleQuote\LaravelApiResource\Rules\ValidateArrayKeys;
use SingleQuote\LaravelApiResource\Rules\ValidateHasParameter;

use function config;

class ApiRequestService
{
    /**
     * In-process memo of the built rule sets, keyed by "model|limit".
     *
     * The output of defaults() is fully determined by the model class, the
     * configured api.limit and the model's fillables/relations (themselves
     * stable per class). Both variable inputs are part of the key, so a memo
     * hit is always correct — even when a project varies api.limit per request.
     * Cleared on deploy/worker restart. Consumers receive a copy-on-write array
     * (PHP value semantics); the shared Rule objects are stateless.
     *
     * @var array<string, array>
     */
    private static array $defaultsMemo = [];

    /**
     * @param string $model
     * @return array
     */
    public static function defaults(string $model): array
    {
        $limit = config('laravel-api-resource.api.limit', 1000);
        $memoKey = $model . '|' . $limit;

        if (isset(self::$defaultsMemo[$memoKey])) {
            return self::$defaultsMemo[$memoKey];
        }

        $fillables = ApiModel::getFillable($model);
        $relations = ApiModel::getRelations($model);

        return self::$defaultsMemo[$memoKey] = [
            // == Pagination & Sorting ==
            'limit' => ['nullable', 'int', 'min:1', 'max:' . $limit],
            'orderBy' => ['nullable', 'string', new OrderByRule($model)],
            'orderByDesc' => ['nullable', 'string', new OrderByRule($model)],

            // == Selection & Loading ==
            'select' => 'nullable|array',
            'select.*' => 'nullable|string|in:' . $fillables,

            'with' => ['nullable', 'array', new ValidateArrayKeys($relations)],
            'with.*' => 'nullable',

            'withCount' => 'nullable|array',
            'withCount.*' => 'nullable|string|in:' . $relations,

            // == Searching ==
            'search' => 'nullable|array',
            'search.fields' => 'nullable|array',
            'search.fields.*' => 'nullable|string|in:'.$fillables,
            'search.query' => 'nullable|string|min:2|max:191',

            // == Filtering (Where) ==
            'where' => ['nullable', 'array', new ValidateArrayKeys($fillables)],
            'where.*' => ['nullable', new MixedRule()],

            'orWhere' => ['nullable', 'array', new ValidateArrayKeys($fillables)],
            'orWhere.*' => ['nullable', new MixedRule()],

            'whereJsonContains' => ['nullable', 'array', new ValidateArrayKeys($fillables)],
            'whereJsonContains.*' => ['nullable', new MixedRule()],

            'whereJsonDoesntContain' => ['nullable', 'array', new ValidateArrayKeys($fillables)],
            'whereJsonDoesntContain.*' => ['nullable', new MixedRule()],

            'whereIn' => ['nullable', 'array', new ValidateArrayKeys($fillables)],
            'whereIn.*' => 'nullable|array',

            'whereNotIn' => ['nullable', 'array', new ValidateArrayKeys($fillables)],
            'whereNotIn.*' => 'nullable|array',

            'whereNull' => 'nullable|array',
            'whereNull.*' => ['nullable', 'string', new FillableRule($fillables)],

            'whereNotNull' => 'nullable|array',
            'whereNotNull.*' => ['nullable', 'string', new FillableRule($fillables)],

            // == Relation Filtering ==
            'has' => ['nullable', 'array', new ValidateHasParameter($relations)],
            'has.*' => 'nullable',

            'doesntHave' => 'nullable|array',
            'doesntHave.*' => 'nullable|string|in:' . $relations,

            'whereRelation' => ['nullable', 'array', new ValidateArrayKeys($relations)],
            'whereRelation.*' => 'required|array',
            'whereRelation.*.*' => ['required', new MixedRule()],

            // == Soft Deletes ==
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
