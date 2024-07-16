<?php
/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace SingleQuote\LaravelApiResource\Infra;

use Illuminate\Database\Eloquent\Model;
use Throwable;

use function collect;

/**
 * Description of Operator
 *
 * @author wim_p
 */
class ApiModel
{
    /**
     * @param string $operator
     * @return string
     */
    public static function getRelations(string $modelClass, bool $withSubRelations = true): string
    {
        $model     = (new $modelClass());
        $relations = $model->definedRelations();

        $additionalRelations = $withSubRelations ? ($model->apiRelations ?? []) : [];

        foreach ($relations as $relation) {

            if(! $withSubRelations) {
                continue;
            }

            $additionalRelations[] = $relation;

            try {
                $relationModel = $model->$relation()->getModel();
            } catch (Throwable $ex) {
                continue;
            }


            if (isset($relationModel->apiRelations)) {
                $additionalRelations = [
                    ... $additionalRelations,
                    ... collect($relationModel->apiRelations)->map(fn ($r) => "$relation.$r")->toArray()
                ];
            }
        }

        $apiIncluded = array_merge($relations, $additionalRelations);

        return collect($apiIncluded)->unique()->implode(',');
    }

    /**
     * @param string $model
     * @return string
     */
    public static function getFillable(string|Model $modelClass): string
    {
        if($modelClass instanceof Model) {
            $model = $modelClass;
        } else {
            if (!class_exists($modelClass)) {
                return '';
            }

            $model = (new $modelClass());
        }

        $fillables = [
            'id',
            ...$model->getFillable(),
        ];

        $hidden = $model->getHidden();

        return collect($fillables)->filter(function ($fill) use ($hidden) {
            return !in_array($fill, $hidden);
        })->implode(',');
    }
}
