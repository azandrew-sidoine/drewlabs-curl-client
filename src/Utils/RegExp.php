<?php

namespace Drewlabs\Curl\Utils;

class RegExp
{
    /** @var string */
    public const JSON_PATTERN = '/^(?:application|text)\/(?:[a-z]+(?:[\.-][0-9a-z]+){0,}[\+\.]|x-)?json(?:-[a-z]+)?/i';

    /**
     * Returns a boolean value indicating whether the text string matches json or not.
     *
     * @return bool
     */
    public static function matchJson(string $text)
    {
        return false !== preg_match(self::JSON_PATTERN, $text);
    }
}