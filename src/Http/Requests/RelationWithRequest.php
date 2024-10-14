<?php

namespace SingleQuote\LaravelApiResource\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RelationWithRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {

        return [
            ... \SingleQuote\LaravelApiResource\Service\ApiRequestService::defaults(Entity::class),
        ];
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            ... \SingleQuote\LaravelApiResource\Service\ApiRequestService::attributes(Entity::class),
        ];
    }
}
