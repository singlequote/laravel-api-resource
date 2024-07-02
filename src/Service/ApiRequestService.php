<?php

namespace SingleQuote\LaravelApiResource\Service;

use SingleQuote\LaravelApiResource\Rules\MixedRule;
use Throwable;
use function collect;
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

            // Set WhereHas
            'has' => 'nullable|array',
            'has.*' => 'required|string|in:' . self::getRelations($model),

            // Set Where Relation
            'whereRelation' => 'nullable|array',
            'whereRelation.*' => 'required|array',
            'whereRelation.*.*' => ['required', new MixedRule()],

            // Set With relations
            'with' => 'nullable|array',
            'with.*' => 'required|string|in:' . self::getRelations($model),

            // Set select on columns
            'select' => 'nullable|array',
            'select.*' => 'required|string|in:' . self::getFillable($model),

            // Set the order
            'orderBy' => 'nullable|string|in:updated_at,created_at,' . self::getFillable($model),
            'orderByDesc' => 'nullable|string|in:updated_at,created_at,' . self::getFillable($model),
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

    /**
     * @param string $modelClass
     * @return string
     */
    public static function getRelations(string $modelClass): string
    {
        try{
            $model = (new $modelClass);
            $relations = $model->definedRelations();
        } catch (Throwable $ex) {
            return '';
        }
        
        $additionalRelations = $model->apiRelations ?? [];
        
        foreach($relations as $relation){

            $additionalRelations[] = $relation;
            $relationModel = $model->$relation()->getModel();
            
            if(isset($relationModel->apiRelations)){
                $additionalRelations = [
                    ... $additionalRelations,
                    ... collect($relationModel->apiRelations)->map(fn($r) => "$relation.$r")->toArray()
                ];
            }      
        }
                
        $apiIncluded = array_merge($relations, $additionalRelations);

        return collect($apiIncluded)->unique()->implode(',');
    }

    /**
     * @param string $model
     * @return string
     */
    public static function getFillable(string $model): string
    {
        if (!class_exists($model)) {
            return '';
        }

        $fillables = [
            'id',
            ...(new $model())->getFillable(),
        ];

        $hidden = (new $model())->getHidden();

        return collect($fillables)->filter(function ($fill) use ($hidden) {
            return !in_array($fill, $hidden);
        })->implode(',');
    }
}
