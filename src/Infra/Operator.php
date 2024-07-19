<?php

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
            case 'contains':
                return 'LIKE';
            case 'startsWith':
                return 'LIKE%';
            case 'endsWith':
                return '%LIKE';
            case 'notContains':
                return 'NOT LIKE';
            case 'notEqual':
                return '!=';
            default:
                return '=';
        }
    }

    /**
     * @return array
     */
    public static function allowed(): array
    {
        return [
            'gt',
            'gte',
            'lt',
            'lte',
            'in',
            'eq',
            'startsWith',
            'endsWith',
            'notContains',
            'contains',
            'equals',
            'notEqual',
        ];
    }
}
