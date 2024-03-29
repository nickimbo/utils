<?php

namespace Nickimbo\Utils\Interfaces;

use stdClass;

interface RuleInterface {
    
    public function run(stdClass|array|string|int $needle, string $field): bool;

}

?>