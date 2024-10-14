<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use SingleQuote\LaravelApiResource\Http\Requests\RelationWithRequest;
use SingleQuote\LaravelApiResource\Service\ApiRequestService;

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

        if(count($relations) === 0) {
            return $builder;
        }

        foreach($relations as $relation => $data) {
            if(is_string($relation) && is_array($data)) {
                self::parseRelationBuilder($builder, $request, $relation, $data);
                continue;
            }

            if(is_string($relation) && (bool) $data === true) {
                $builder->with($relation);
                continue;
            }

            $builder->with($data);
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     * @param FormRequest|Request $request
     * @param string $relation
     * @param array $data
     * @return Builder
     */
    public static function parseRelationBuilder(Builder &$builder, FormRequest|Request $request, string $relation, array $data): Builder
    {
        return $builder->with([$relation => function (Relation $query) use ($request, $data) {
            $query->apiDefaults(self::createFormRequestValidator($request, $query, $data));
        }]);
    }

    /**
     *
     * @param FormRequest|Request $request
     * @param Relation $query
     * @param array $data
     * @return RelationWithRequest
     */
    public static function createFormRequestValidator(FormRequest|Request $request, Relation $query, array $data): RelationWithRequest
    {
        $validator = Validator::make($data, ApiRequestService::defaults($query->getModel()::class));

        $newRequest = new RelationWithRequest();

        $newRequest->setUserResolver(function () use ($request) {
            return $request->user();
        });

        $newRequest->merge($data);
        $newRequest->setValidator($validator);

        return $newRequest;
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
