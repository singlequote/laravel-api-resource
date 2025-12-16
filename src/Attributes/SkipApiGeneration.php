<?php

declare(strict_types=1);

namespace SingleQuote\LaravelApiResource\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class SkipApiGeneration
{
    public const ALL = 'all';
    public const ACTIONS = 'actions';
    public const REQUESTS = 'requests';
    public const RESOURCE = 'resource';

    /**
     * @param string ...$skips
     */
    public function __construct(
        public array $skips = [self::ALL]
    ) {
    }
}
