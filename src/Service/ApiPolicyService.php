<?php

declare(strict_types=1);

namespace SingleQuote\LaravelApiResource\Service;

use Illuminate\Database\Eloquent\Model;

use function auth;

final class ApiPolicyService
{
    /**
     * @param Model $model
     * @param array $addition
     * @return array
     */
    public static function defaults(Model $model, array $addition = []): array
    {
        $keys = collect($addition)->flatMap(function (string $abbility) use ($model) {
            return [
                $abbility => auth()->user()->can($abbility, $model),
            ];
        })->toArray();

        return [
            'viewAny' => auth()->user()->can('viewAny', $model::class),
            'view' => auth()->user()->can('view', $model),
            'create' => auth()->user()->can('create', $model::class),
            'update' => auth()->user()->can('update', $model),
            'delete' => auth()->user()->can('delete', $model),
            'restore' => auth()->user()->can('restore', $model),
            'forceDelete' => auth()->user()->can('forceDelete', $model),
            ... $keys,
        ];
    }
}
