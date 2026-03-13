<?php

namespace Drewlabs\Curl;

/** @deprecated use implementation from utils namespace */
class RegExp
{

    /** @var string */
    const JSON_PATTERN = '/^(?:application|text)\/(?:[a-z]+(?:[\.-][0-9a-z]+){0,}[\+\.]|x-)?json(?:-[a-z]+)?/i';

    /**
     * returns a boolean value indicating whether the text string matches json or not
     * 
     * @param string $text 
     * 
     * @return bool 
     */
    public static function matchJson(string $text)
    {
        return false !== preg_match(static::JSON_PATTERN, $text);
    }
}