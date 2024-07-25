<?php

namespace SingleQuote\LaravelApiResource\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Description of ScopeWhere
 *
 * @author wim_p
 */
class ScopeTrashed
{
    /**
     * @param Builder|QueryBuilder $builder
     * @param FormRequest $request
     * @return Builder|QueryBuilder
     */
    public static function handle(Builder|QueryBuilder $builder, FormRequest $request): Builder|QueryBuilder
    {
        if($request->validated('withTrashed', false)) {
            return $builder->withTrashed();
        }
        if($request->validated('onlyTrashed', false)) {
            return $builder->onlyTrashed();
        }

        return $builder;
    }

}
