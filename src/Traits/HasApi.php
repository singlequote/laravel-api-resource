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
use SingleQuote\LaravelApiResource\Scopes\ScopeWhere;
use SingleQuote\LaravelApiResource\Scopes\ScopeWhereRelation;
use SingleQuote\LaravelApiResource\Scopes\ScopeWith;

use function collect;
use function str;
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
        ScopeWhere::handle($builder, $request->validated('where', []));
        ScopeWhere::handle($builder, $request->validated('orWhere', []), 'or');
        ScopeHas::handle($builder, $request->validated('has', []));
        ScopeDoesntHave::handle($builder, $request->validated('doesntHave', []));
        ScopeWhereRelation::handle($builder, $request->validated('whereRelation', []));
        ScopeSearch::handle($builder, $request->validated('search', []));
        ScopeSelect::handle($builder, $request->validated('select', []));
        ScopeOrder::handle($builder, $request->validated('orderBy'));
        ScopeOrder::handle($builder, $request->validated('orderByDesc'), 'desc');

        return $builder->parseWhereNotNull($request->validated('whereNotNull'))
            ->parseWhereIn($request->validated('whereIn'));
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

        return $this->load(ScopeWith::getRelations($request));
    }


    /**
     * @param Builder $builder
     * @param string|null $scope
     * @return Builder
     */
    public function scopeParseWhereNotNull(Builder $builder, ?string $scope = null): Builder
    {
        if ($scope) {
            if (in_array(str($scope)->before('->')->value(), $this->getFillable())) {
                $builder->whereNotNull($scope);
            }
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     * @param array|null $scopes
     * @return Builder
     */
    public function scopeParseWhereIn(Builder $builder, ?array $scopes = []): Builder
    {
        foreach ($scopes ?? [] as $column => $scope) {

            if (!in_array(str($column)->before('->')->value(), [...$this->getFillable(), 'id']) || !is_array($scope)) {
                continue;
            }

            $builder->whereIn($column, $scope);
        }

        return $builder;
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
