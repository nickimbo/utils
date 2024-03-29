<?php

namespace Nickimbo\Utils\Validator\Rules;

use Nickimbo\Utils\Interfaces\RuleInterface;
use Nickimbo\Utils\Validator\Exceptions;


use \stdClass;


class LengthRange implements RuleInterface {

    private array $Range;

    public function __construct(array $range) {
        if(@sizeof($range) !== 2) throw new Exceptions\InvalidRange($range);

        array_map(function($val) use($range) {
            if(@is_int($val) === false) throw new Exceptions\InvalidRange($range);
        }, $range);

        $this->Range = $range;
    }

    public function run(stdClass|array|string|int $needle, string $field): bool {

        $Length = @in_array(@gettype($needle), ['string', 'int']) ? @strlen((string) $needle) : @sizeof((array) $needle);

        return $Length >= $this->Range[0] && $Length <= $this->Range[1];
    }
}



?>