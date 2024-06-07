<?php

namespace SingleQuote\LaravelApiResource\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use ReflectionClass;

use function collect;
use function str;
use function str_contains;

trait HasApi
{
    /**
     * @param Builder $builder
     * @param FormRequest $request
     * @return Builder
     */
    public function scopeApiDefaults(Builder $builder, FormRequest $request): Builder
    {
        return $builder->with($this->getRelationWith($request))
                ->parseSearch($request->validated('search'))
                ->parseOrderBy($request->validated('orderBy'))
                ->parseOrderByDesc($request->validated('orderByDesc'))
                ->parseSelect($request->validated('select'))
                ->parseWhere($request->validated('where'))
                ->parseOrWhere($request->validated('orWhere'))
                ->parseWhereNotNull($request->validated('whereNotNull'))
                ->parseWhereIn($request->validated('whereIn'))
                ->parseWhereNotIn($request->validated('whereNotIn'))
                ->parseHas($request->validated('has'))
                ->parseWhereRelation($request->validated('whereRelation'));
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

        return $this->load($this->getRelationWith($request));
    }

    /**
     * @param Builder $builder
     * @param array|null $searchable
     * @return Builder
     */
    public function scopeParseSearch(Builder $builder, ?array $searchable = []): Builder
    {
        if (!$searchable) {
            return $builder;
        }

        return $builder->where(function (Builder $builder) use ($searchable) {

            $this->applySearch($builder, $searchable);

            return $builder;
        });
    }

    /**
     * @param Builder $builder
     * @param array $searchable
     * @return Builder
     */
    private function applySearch(Builder &$builder, array $searchable): Builder
    {
        foreach ($searchable['fields'] ?? [] as $column) {

            if (str($column)->contains('|')) {
                $builder = $this->searchRelation($builder, str($column)->before('|'), str($column)->after('|'), str($searchable['query'])->lower());

                continue;
            }

            $key = str($column)->replace('.', '->')->value();

            if (!in_array(str($key)->before('->')->value(), [...$this->getFillable(), 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $search = str($searchable['query'])->lower()->explode(' ');

            foreach ($search as $searchKey) {
                $builder = $builder->orWhereRaw("LOWER($key) LIKE ?", ["%{$searchKey}%"]);
            }
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     * @param string $relation
     * @param string $column
     * @param string $search
     * @return Builder
     */
    private function searchRelation(Builder $builder, string $relation, string $column, string $search): Builder
    {
        return $builder->orWhereHas($relation, function (Builder $builder) use ($column, $search) {
            $builder->whereRaw("LOWER($column) LIKE ?", ["%{$search}%"]);
        });
    }

    /**
     * @param Builder $builder
     * @param string|null $order
     * @return Builder
     */
    public function scopeParseOrderBy(Builder $builder, ?string $order): Builder
    {
        if ($order) {
            $builder->orderBy($order);
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     * @param string|null $order
     * @return Builder
     */
    public function scopeParseOrderByDesc(Builder $builder, ?string $order): Builder
    {
        if ($order) {
            $builder->orderByDesc($order);
        }

        return $builder;
    }

    /**
     * @param FormRequest|Request $request
     * @return array
     */
    private function getRelationWith(FormRequest|Request $request): array
    {
        if ($request instanceof FormRequest) {
            return $request->validated('with', []);
        }

        return $request->get('with', []);
    }

    /**
     * @param Builder $builder
     * @param array|null $scopes
     * @return Builder
     */
    public function scopeParseSelect(Builder $builder, ?array $scopes = []): Builder
    {
        if (count($scopes ?? []) === 0) {
            return $builder;
        }

        $builder->select('id');

        foreach ($scopes ?? [] as $column) {

            $key = str($column)->replace('.', '->')->value();

            if (!in_array(str($key)->before('->')->value(), [...$this->getFillable(), 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $builder->addSelect($key);
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     * @param array|null $scopes
     * @return Builder
     */
    public function scopeParseOrWhere(Builder $builder, ?array $scopes = []): Builder
    {
        return $this->scopeParseWhere($builder, $scopes, 'or');
    }

    /**
     * @param Builder $builder
     * @param array|null $scopes
     * @param string $boolean
     * @return Builder
     */
    public function scopeParseWhere(Builder $builder, ?array $scopes = [], string $boolean = 'and'): Builder
    {
        foreach ($scopes ?? [] as $column => $scope) {

            $operator = is_array($scope) ? $this->parseOperator(array_key_first($scope)) : '=';
            $value = is_array($scope) ? $scope[array_key_first($scope)] : $scope;

            $key = str($column)
                ->replace('.', '->')
                ->value();

            if (!in_array(str($key)->before('->')->value(), $this->getFillable())) {
                continue;
            }

            if ($scope === 'null') {
                $builder->whereNull($key);
            } else {
                $builder->where($key, $operator, $value, $boolean);
            }
        }

        return $builder;
    }

    /**
     * @param string $operator
     * @return string
     */
    private function parseOperator(string $operator): string
    {
        switch($operator) {
            case 'gt':
                return '>';
            case 'gte':
                return '>=';
            case 'lt':
                return '<';
            case 'lte':
                return '<=';
            default:
                return '=';
        }
    }

    /**
     * @param Builder $builder
     * @param string|null $scope
     * @return Builder
     */
    public function scopeParseWhereNotNull(Builder $builder, ?string $scope = null): Builder
    {
        if ($scope) {
            $key = str($scope)->replace('.', '->')->value();

            if (in_array(str($key)->before('->')->value(), $this->getFillable())) {
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

            $key = str($column)
                ->replace('.', '->')
                ->value();

            if (!in_array(str($key)->before('->')->value(), [...$this->getFillable(), 'id']) || !is_array($scope)) {
                continue;
            }

            $builder->whereIn($key, $scope);
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     * @param array|null $scopes
     * @return Builder
     */
    public function scopeParseWhereNotIn(Builder $builder, ?array $scopes = []): Builder
    {
        foreach ($scopes ?? [] as $column => $scope) {

            $key = str($column)
                ->replace('.', '->')
                ->value();

            if (!in_array(str($key)->before('->')->value(), [...$this->getFillable(), 'id']) || !is_array($scope)) {
                continue;
            }

            $builder->whereNotIn($key, $scope);
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     * @param array|null $scopes
     * @return Builder
     */
    public function scopeParseHas(Builder $builder, ?array $scopes = []): Builder
    {
        foreach ($scopes ?? [] as $scope) {
            $builder->has($scope);
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     * @param array|null $scopes
     * @return Builder
     */
    public function scopeParseWhereRelation(Builder $builder, ?array $scopes = []): Builder
    {
        foreach ($scopes ?? [] as $relation => $scope) {

            if (is_array($scope) && in_array($relation, $this->definedRelations())) {
                foreach ($scope as $column => $scopeValue) {
                    $operator = is_array($scopeValue) ? $this->parseOperator(array_key_first($scopeValue)) : '=';
                    $value = is_array($scopeValue) ? $scopeValue[array_key_first($scopeValue)] : $scopeValue;

                    $key = str($column)
                        ->replace('.', '->')
                        ->value();

                    if ($operator !== '=' && $value === null) {
                        continue;
                    }

                    if ($value === null) {
                        $builder->whereRelation($relation, $key, $value);
                    }

                    if ($value !== null) {
                        $builder->whereRelation($relation, $key, $operator, $value);
                    }
                }
            }
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
