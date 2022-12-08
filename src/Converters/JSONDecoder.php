<?php

namespace Drewlabs\Curl\Converters;

use JsonException;

// Define the JSON_THROW_ON_ERROR global constant if not exists
if (!defined('JSON_THROW_ON_ERROR')) {
    defined('JSON_THROW_ON_ERROR', 4194304);
}

/**
 * @package \Drewlabs\Curl\Converters
 */
class JSONDecoder
{

    /**
     * 
     * @var bool
     */
    private $associative;

    /**
     * 
     * @var int
     */
    private $depth = 512;

    /**
     * 
     * @var int
     */
    private $flags = JSON_THROW_ON_ERROR;

    /**
     * Creates a {@see \Drewlabs\TxnClient\JSONDecoder} instance
     * 
     * @param bool $associative 
     * @param null|int $depth 
     * @param int $flags 
     */
    public function __construct($associative = true, ?int $depth = 512, int $flags = 0)
    {
        $this->associative = $associative;
        $this->depth = $depth ?? 512;
        $this->flags =  $flags ?? JSON_THROW_ON_ERROR;
    }

    /**
     * Decode a JSON string to a PHP array (dictionnary) or a PHP object
     * 
     * @param string $value 
     * 
     * @throws JsonException
     * 
     * @return object|array 
     */
    public function decode(string $value)
    {
        return @json_decode($value, $this->associative, $this->depth, $this->flags);
    }
}
