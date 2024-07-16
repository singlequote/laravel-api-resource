<?php
/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace SingleQuote\LaravelApiResource\Infra;

/**
 * Description of Operator
 *
 * @author wim_p
 */
class Operator
{
    /**
     * @param string $operator
     * @return string
     */
    public static function toSql(string $operator): string
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
            case 'in':
                return 'LIKE';
            default:
                return '=';
        }
    }
}
