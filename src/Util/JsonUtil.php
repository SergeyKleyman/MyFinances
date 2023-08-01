<?php

declare(strict_types=1);

namespace MyFinances\Util;

use const JSON_PRETTY_PRINT;

final class JsonUtil
{
    use StaticClassTrait;

    public static function encode(mixed $data, bool $prettyPrint = false): string
    {
        $options = 0;
        $options |= $prettyPrint ? JSON_PRETTY_PRINT : 0;
        $encodedData = json_encode($data, $options);
        if ($encodedData === false) {
            throw new JsonException(
                'json_encode() failed'
                . '. json_last_error_msg(): ' . json_last_error_msg()
                . '. dataType: ' . get_debug_type($data)
            );
        }
        return $encodedData;
    }
}
