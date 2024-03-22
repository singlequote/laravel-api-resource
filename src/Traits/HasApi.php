<?php

namespace SingleQuote\LaravelApiResource\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use ReflectionClass;

use function collect;
use function str;
use function str_contains;

trait HasApi
{
    public function scopeApiDefaults(Builder $builder, FormRequest $request): Builder
    {
        return $builder->with($this->getRelationWith($request))
            ->parseSearch($request->validated('search'))
            ->parseOrderBy($request->validated('orderBy'))
            ->parseOrderByDesc($request->validated('orderByDesc'))
            ->parseSelect($request->validated('select'))
            ->parseWhere($request->validated('where'))
            ->parseWhereIn($request->validated('whereIn'))
            ->parseWhereNotIn($request->validated('whereNotIn'))
            ->parseHas($request->validated('has'))
            ->parseWhereRelation($request->validated('whereRelation'));
    }

    public function modelDefaults(FormRequest $request): self
    {
        if (! $this->exists) {
            return $this;
        }

        return $this->load($this->getRelationWith($request));
    }

    public function scopeParseSearch(Builder $builder, ?array $searchable = []): Builder
    {
        if (! $searchable) {
            return $builder;
        }

        return $builder->where(function (Builder $builder) use ($searchable) {

            foreach ($searchable['fields'] ?? [] as $column) {

                if (str($column)->contains('|')) {
                    $builder = $this->searchRelation($builder, str($column)->before('|'), str($column)->after('|'), str($searchable['query'])->lower());

                    continue;
                }

                $key = str($column)->replace('.', '->')->value();

                if (! in_array(str($key)->before('->')->value(), [...$this->getFillable(), 'created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }

                $search = str($searchable['query'])->lower();

                $builder = $builder->orWhereRaw("LOWER($key) LIKE ?", ["%{$search}%"]);
            }

            return $builder;
        });
    }

    private function searchRelation(Builder $builder, string $relation, string $column, string $search): Builder
    {
        return $builder->orWhereHas($relation, function (Builder $builder) use ($column, $search) {
            $builder->whereRaw("LOWER($column) LIKE ?", ["%{$search}%"]);
        });
    }

    public function scopeParseOrderBy(Builder $builder, ?string $order): Builder
    {
        if ($order) {
            $builder->orderBy($order);
        }

        return $builder;
    }

    public function scopeParseOrderByDesc(Builder $builder, ?string $order): Builder
    {
        if ($order) {
            $builder->orderByDesc($order);
        }

        return $builder;
    }

    private function getRelationWith(FormRequest $request): array
    {
        return $request->validated('with', []);
    }

    public function scopeParseSelect(Builder $builder, ?array $scopes = []): Builder
    {
        if (count($scopes ?? []) === 0) {
            return $builder;
        }

        $builder->select('id');

        foreach ($scopes ?? [] as $column) {

            $key = str($column)->replace('.', '->')->value();

            if (! in_array(str($key)->before('->')->value(), [...$this->getFillable(), 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $builder->addSelect($key);
        }

        return $builder;
    }

    public function scopeParseWhere(Builder $builder, ?array $scopes = []): Builder
    {
        foreach ($scopes ?? [] as $column => $scope) {

            $key = str($column)
                ->replace('.', '->')
                ->value();

            if (! in_array(str($key)->before('->')->value(), $this->getFillable())) {
                continue;
            }

            if ($scope === 'null') {
                $builder->whereNull($key);
            } else {
                $builder->where($key, $scope);
            }
        }

        return $builder;
    }

    public function scopeParseWhereIn(Builder $builder, ?array $scopes = []): Builder
    {
        foreach ($scopes ?? [] as $column => $scope) {

            $key = str($column)
                ->replace('.', '->')
                ->value();

            if (! in_array(str($key)->before('->')->value(), [...$this->getFillable(), 'id']) || ! is_array($scope)) {
                continue;
            }

            $builder->whereIn($key, $scope);
        }

        return $builder;
    }

    public function scopeParseWhereNotIn(Builder $builder, ?array $scopes = []): Builder
    {
        foreach ($scopes ?? [] as $column => $scope) {

            $key = str($column)
                ->replace('.', '->')
                ->value();

            if (! in_array(str($key)->before('->')->value(), [...$this->getFillable(), 'id']) || ! is_array($scope)) {
                continue;
            }

            $builder->whereNotIn($key, $scope);
        }

        return $builder;
    }

    public function scopeParseHas(Builder $builder, ?array $scopes = []): Builder
    {
        foreach ($scopes ?? [] as $scope) {
            $builder->has($scope);
        }

        return $builder;
    }

    public function scopeParseWhereRelation(Builder $builder, ?array $scopes = []): Builder
    {
        foreach ($scopes ?? [] as $key => $scope) {
            if (is_array($scope) && in_array($key, $this->definedRelations())) {
                $builder->whereRelation($key, array_key_first($scope), $scope[array_key_first($scope)]);
            }
        }

        return $builder;
    }

    public function definedRelations(): array
    {
        $reflector = new ReflectionClass(get_called_class());

        return collect($reflector->getMethods())
            ->filter(function ($method) {
                return ! empty($method->getReturnType()) && ! empty($method->getReturnType()) && str_contains($method->getReturnType(), 'Illuminate\Database\Eloquent\Relations');
            })
            ->pluck('name')
            ->all();
    }

    public function scopeScopeWithAll(Builder $query): Builder
    {
        return $query->with($this->definedRelations());
    }

    public function scopeScopeLoadAll(): self
    {
        return $this->load($this->definedRelations());
    }
}
