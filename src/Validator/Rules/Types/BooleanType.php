<?php

namespace Nickimbo\Utils\Validator\Rules\Types;


use Nickimbo\Utils\Validator\Interfaces\RuleInterface;

class BooleanType implements RuleInterface {
    
    public function run(mixed $needle, string $field): bool {
        return @is_bool($needle);
    }

}