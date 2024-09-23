<?php

namespace SingleQuote\LaravelApiResource\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use ReflectionClass;
use SingleQuote\LaravelApiResource\Scopes\ScopeDoesntHave;
use SingleQuote\LaravelApiResource\Scopes\ScopeHas;
use SingleQuote\LaravelApiResource\Scopes\ScopeOrder;
use SingleQuote\LaravelApiResource\Scopes\ScopeSearch;
use SingleQuote\LaravelApiResource\Scopes\ScopeSelect;
use SingleQuote\LaravelApiResource\Scopes\ScopeTrashed;
use SingleQuote\LaravelApiResource\Scopes\ScopeWhere;
use SingleQuote\LaravelApiResource\Scopes\ScopeWhereIn;
use SingleQuote\LaravelApiResource\Scopes\ScopeWhereNotIn;
use SingleQuote\LaravelApiResource\Scopes\ScopeWhereNotNull;
use SingleQuote\LaravelApiResource\Scopes\ScopeWhereRelation;
use SingleQuote\LaravelApiResource\Scopes\ScopeWith;
use SingleQuote\LaravelApiResource\Scopes\ScopeWithCount;

use function collect;
use function str_contains;

trait HasApi
{
    /**
     * @param Builder $builder
     * @param FormRequest $request
     * @return Builder|QueryBuilder
     */
    public function scopeApiDefaults(Builder $builder, FormRequest $request): Builder|QueryBuilder
    {
        ScopeWith::handle($builder, $request);
        ScopeWithCount::handle($builder, $request);
        ScopeWhere::handle($builder, $request->validated('where', []));
        ScopeWhere::handle($builder, $request->validated('orWhere', []), 'or');
        ScopeHas::handle($builder, $request->validated('has', []));
        ScopeWhereIn::handle($builder, $request->validated('whereIn', []));
        ScopeWhereNotIn::handle($builder, $request->validated('whereNotIn', []));
        ScopeWhereNotNull::handle($builder, $request->validated('whereNotNull', []));
        ScopeDoesntHave::handle($builder, $request->validated('doesntHave', []));
        ScopeWhereRelation::handle($builder, $request->validated('whereRelation', []));
        ScopeSearch::handle($builder, $request->validated('search', []));
        ScopeSelect::handle($builder, $request->validated('select', []));
        ScopeOrder::handle($builder, $request->validated('orderBy'));
        ScopeOrder::handle($builder, $request->validated('orderByDesc'), 'desc');
        ScopeTrashed::handle($builder, $request);

        return $builder;
    }

    /**
     * @param FormRequest|Request $request
     * @return self
     */
    public function modelDefaults(FormRequest|Request $request): self
    {
        if (!$this->exists) {
            return $this;
        }

        return $this->load(ScopeWith::getRelations($request))
            ->loadCount(ScopeWithCount::getRelations($request));
    }

    /**
     * @return array
     */
    public function definedRelations(): array
    {
        $reflector = new ReflectionClass(get_called_class());

        return collect($reflector->getMethods())
                ->filter(function ($method) {
                    return !empty($method->getReturnType()) && !empty($method->getReturnType()) && str_contains($method->getReturnType(), 'Illuminate\Database\Eloquent\Relations');
                })
                ->pluck('name')
                ->all();
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeScopeWithAll(Builder $query): Builder
    {
        return $query->with($this->definedRelations());
    }

    /**
     * @return self
     */
    public function scopeScopeLoadAll(): self
    {
        return $this->load($this->definedRelations());
    }
}
