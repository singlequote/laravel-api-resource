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
        // On public/unauthenticated endpoints there is no user; calling ->can()
        // on null would fatal. Fall back to denying every ability instead.
        $user = auth()->user();

        $keys = collect($addition)->flatMap(function (string $abbility) use ($user, $model) {
            return [
                $abbility => $user?->can($abbility, $model) ?? false,
            ];
        })->toArray();

        return [
            'viewAny' => $user?->can('viewAny', $model::class) ?? false,
            'view' => $user?->can('view', $model) ?? false,
            'create' => $user?->can('create', $model::class) ?? false,
            'update' => $user?->can('update', $model) ?? false,
            'delete' => $user?->can('delete', $model) ?? false,
            'restore' => $user?->can('restore', $model) ?? false,
            'forceDelete' => $user?->can('forceDelete', $model) ?? false,
            ... $keys,
        ];
    }
}
