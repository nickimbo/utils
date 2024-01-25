<?php

namespace Nickimbo\Utils;

define('UTILS_CURRENT_URL', (
    ($_SERVER['REQUEST_SCHEME'] ?? 'http') .
    ('://') . 
    ($_SERVER['HTTP_HOST'] ?? 'localhost') . 
    ($_SERVER['REQUEST_URI'] ?? '/')
));

define('FALSE_PARSE', 0x4227);

function castString($delimiter,$escaper,$text){$d=preg_quote($delimiter,"~");$e=preg_quote($escaper,"~");$tokens=preg_split('~'.$e.'('.$e.'|'.$d.')(*SKIP)(*FAIL)|(?<='.$d.')~',$text,-1,PREG_SPLIT_NO_EMPTY);$escaperReplacement=str_replace(['\\','$'],['\\\\','\\$'],$escaper);$delimiterReplacement=str_replace(['\\','$'],['\\\\','\\$'],$delimiter);return implode(preg_replace(['~\\\\.(*SKIP)(*FAIL)|'.($escaper.$delimiter).'~s','~'.$e.$d.'~'],['%s',$delimiterReplacement],$tokens));}
class Basic {
    static public function String(string|array $haystack, string|number|bool ...$Args): string|null {
        $Args = array_map('strval', $Args);
        if(gettype($haystack) === 'array') {
            if(!$haystack['String'] || gettype($haystack['String']) !== 'string') return null;
            $Str = castString($haystack['Delimiter'] ?? '{}', $haystack['Escaper'] ?? '\\', $haystack['String']);
        }
        else {
            $Str = castString('{}', '\\', $haystack);
        }
        return vsprintf($Str, $Args);
    }
    static public function Find(mixed $needle, array | object $haystack, int $type = 0): array {
        $haystack = (array) $haystack;
        if(!in_array($type, [0, 1])) return ['errors' => [
            'Function argument[2] (Type) is different than available options (0, 1)
            0 = String contains;
            1 = String matches;'
        ]];
        $Ret = [
            'Key' => 0,
            'Value' => null,
            'Found' => false,
            'errors' => []
        ];
        if($type === 0) {
            array_walk(
                $haystack,
                function($Value, $Key) use(&$Ret, $needle): void {
                    if(in_array(gettype($Value), ['string', 'int', 'number'])) {
                        if(str_contains($Value, $needle) === true) {
                            $Ret['Key'] = $Key;
                            $Ret['Value'] = $Value;
                            $Ret['Found'] = true;
                        }
                    }
                }
            );
        }
        else {
            $retrieveKey = array_search($needle, $haystack);
            if(is_numeric($retrieveKey)) {
                $Ret['Key'] = $retrieveKey;
                $Ret['Value'] = $haystack[$retrieveKey];
                $Ret['Found'] = true;
            }
        }
        return $Ret;
    }
    static public function Url(
        string | number | array ...$Args): string | UTILS_CURRENT_URL {
        if (sizeof($Args) < 1) return UTILS_CURRENT_URL;

        if (gettype($Args[0]) === 'array') {
            return UTILS_CURRENT_URL . '?' . http_build_query($Args[0]);
        } else {
            if(isset($Args[1])) {
                if(gettype($Args[1]) === 'array') {
                    if(!isset($Args[2])) return $Args[0] . '?' . http_build_query($Args[1]);
                    else return vsprintf(castString('{}', '\\', $Args[0]), $Args[1]) . '?' . http_build_query($Args[2]);
                }
            }
        }
        return $Args[0];
    }
    static public function Parse(mixed $haystack, string $forceType = 'json'): mixed {
        $T = in_array($forceType, ['json', 'object', 'boolean', 'integer', 'double', 'string', 'array', 'null', 'unknown']) ? ($forceType === 'json' ? 'object' : ($forceType === 'unknown' ? 'unknown type' : $forceType)) : 'object';
        $O = strtolower(strval(gettype($haystack)));

        if($T === $O) return $haystack;

        switch($T):
            case 'object':
                if($forceType === 'json') return json_validate($haystack) ? json_decode($haystack) : FALSE_PARSE;
                return (object)$haystack;
            case 'boolean':
                return boolval($haystack);
            case 'integer':
                return intval($haystack);
            case 'double':
                return doubleval($haystack);
            case 'string':
                return strval($haystack);
            case 'array':
                return (array)$haystack;
            case 'null':
                return is_null($haystack) ? $haystack : FALSE_PARSE;
            case 'unknown type':
                return 'unknown';
        endswitch;
    }
    static public function RequireMulti(string ...$Args): array {
        $metaData = [
            'skipped' => [],
            'required' => [],
            'invalid_dir' => []
        ];
        $functionArgs = array_filter($Args, function($o, $a) {
            $isValid = str_ends_with($o, '.php');
            if(!($isValid === true)) array_push($metaData['skipped'], [
                'argValue' => $o,
                'argPlace' => $a
            ]);
            return $isValid;
        }, ARRAY_FILTER_USE_BOTH);
        foreach($functionArgs as $currentKey => $currentArg) {
            if(!file_exists($currentArg)) {
                array_push($metaData['invalid_dir'], [
                    'argValue' => $currentArg,
                    'argPlace' => $currentKey
                ]);
                return 0;
            }
            require_once($currentArg);
            array_push($metaData['required'], [
                'argValue' => $currentArg,
                'argPlace' => $currentKey
            ]);
        }
        return $metaData;
    }
    static public function RequireAll(string ...$Args): array {
        $metaData = [
            'skipped' => [
                'directories' => [],
                'files' => [] 
            ],
            'required' => []
        ];
        foreach ($Args as $currentDir) {
            if(!realpath($currentDir) OR !is_dir($currentDir)) {
                $metaData['skipped']['directories'][] = [
                    'Path' => $currentDir,
                    'Reason' => 'Path is not a directory or does not exist.'
                ];
                continue;
            };
            $GD = scandir($currentDir);
            $metaData['nigga'] = $GD;
            if (sizeof($GD) === 0) {
                $metaData['skipped']['directories'][] = [
                    'Path' => $currentDir,
                    'Reason' => 'No files detected in directory'
                ];
                continue;
            }
            foreach ($GD as $currentFile) {
                if(!str_ends_with($currentFile, '.php')) {
                    $metaData['skipped']['files'] = [
                        'File' => $currentFile,
                        'Reason' => 'File is not an PHP File.' 
                    ];
                    continue 2;
                }
                require($currentDir . $currentFile);
                $metaData['required'][] = $currentDir . $currentFile;
            }
        }
        return $metaData;
    }
}
?>
