<?php

namespace Services;

class UtilityService
{
    public function getEmoji(string $code)
    {
        $bin = hex2bin(str_repeat('0', 8 - strlen($code)) . $code);
        return mb_convert_encoding($bin, 'UTF-8', 'UTF-32BE');
    }
}
