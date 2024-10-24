<?php

namespace SingleQuote\LaravelApiResource\Infra;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Validator;
use SingleQuote\LaravelApiResource\Http\Requests\RelationWithRequest;
use SingleQuote\LaravelApiResource\Service\ApiRequestService;

use function auth;

/**
 * Description of Action
 *
 * @author wim_p
 */
class ReExecute
{
    /**
     * @param Relation|Builder $query
     * @param array $data
     * @return Relation|Builder
     */
    public static function handle(Relation|Builder $query, array $data): Relation|Builder
    {
        return $query->apiDefaults(self::createFormRequestValidator($query, $data));
    }

    /**
     * @param Relation|Builder $query
     * @param array $data
     * @return RelationWithRequest
     */
    public static function createFormRequestValidator(Relation|Builder $query, array $data): RelationWithRequest
    {
        $validator = Validator::make($data, ApiRequestService::defaults($query->getModel()::class));

        $newRequest = new RelationWithRequest();

        $newRequest->setUserResolver(function () {
            return auth()->user();
        });

        $newRequest->merge($data);
        $newRequest->setValidator($validator);

        return $newRequest;
    }
}
