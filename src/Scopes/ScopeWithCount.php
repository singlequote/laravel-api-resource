<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

/**
 * Description of ScopeWhere
 *
 * @author wim_p
 */
class ScopeWithCount
{
    /**
     * @param Builder|QueryBuilder $builder
     * @param FormRequest|Request $request
     * @return Builder|QueryBuilder
     */
    public static function handle(Builder|QueryBuilder $builder, FormRequest|Request $request): Builder|QueryBuilder
    {
        $relations = self::getRelations($request);

        return $builder->withCount($relations);
    }

    /**
     * @param FormRequest|Request $request
     * @return array
     */
    public static function getRelations(FormRequest|Request $request): array
    {
        if ($request instanceof FormRequest) {
            return $request->validated('withCount', []);
        }

        return $request->get('withCount', []);
    }
}
