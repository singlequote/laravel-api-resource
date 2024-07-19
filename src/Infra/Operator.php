<?php

namespace SingleQuote\LaravelApiResource\Infra;

class Operator
{

    /**
     * @param string $operator
     * @return string
     */
    public static function toSql(string $operator): string
    {
        if (in_array($operator, ['gt', 'greater'])) {
            return '>';
        }
        if (in_array($operator, ['gte', 'greaterEquals'])) {
            return '>=';
        }
        if (in_array($operator, ['lt', 'lesser'])) {
            return '<';
        }
        if (in_array($operator, ['lte', 'lesserEquals'])) {
            return '<=';
        }
        if (in_array($operator, ['in', 'contains'])) {
            return 'LIKE';
        }
        if (in_array($operator, ['nin', 'notContains'])) {
            return 'NOT LIKE';
        }
        if (in_array($operator, ['sw', 'startsWith'])) {
            return 'LIKE%';
        }
        if (in_array($operator, ['ew', 'endsWith'])) {
            return '%LIKE';
        }
        if (in_array($operator, ['eq', 'equals'])) {
            return '=';
        }
        if (in_array($operator, ['neq', 'notEqual'])) {
            return '!=';
        }

        return '=';
    }

    /**
     * @return array
     */
    public static function allowed(): array
    {
        return [
            'gt',
            'greater',
            'gte',
            'greaterEquals',
            'lt',
            'lesser',
            'lte',
            'lesserEquals',
            'in',
            'contains',
            'nin',
            'notContains',
            'sw',
            'startsWith',
            'ew',
            'endsWith',
            'eq',
            'equals',
            'neq',
            'notEqual',
        ];
    }
}
