<?php

namespace Drewlabs\Curl;

use Drewlabs\TxnClient\HasContructorFactory;
use JsonSerializable;

/**
 * 
 * @method static PostData new($data)
 * 
 * @package Drewlabs\TxnClient
 */
class PostData
{
    use HasContructorFactory;

    /**
     * 
     * @var string|array|\JsonSerializable
     */
    private $content;

    /**
     * Creates a {@see \Drewlabs\TxnClient\PostData} instance
     * 
     * @param string|array|\JsonSerializable $data 
     */
    public function __construct($data)
    {
        $this->setContent($data);
    }

    private function setContent($value)
    {
        $this->content = $value;
        return $this;
    }

    /**
     * Returns the raw or internal value of the current instance
     * 
     * @return string|array|JsonSerializable 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Check if the post data is multi dimensional arraay
     *
     * @access public
     * @param  $array
     *
     * @return boolean
     */
    public function isMultiDimensional()
    {
        if (!is_array($this->content)) {
            return false;
        }
        return (bool)count(array_filter($this->content, 'is_array'));
    }

    /**
     * Check if the data is JSON serializable
     * 
     * @return bool 
     */
    public function isJSONSerializable()
    {
        return (is_array($this->content) ||
            (is_object($this->content) &&
                interface_exists('JsonSerializable', false) &&
                $this->content instanceof \JsonSerializable
            )
        );
    }

    /**
     * Return the current instance with data flatten to
     * a 1 dimensional list
     * 
     * @return static 
     */
    public function flatten()
    {
        $object = clone $this;
        return $object->setContent(self::toList($object->getContent()));
    }

    /**
     * Converts input data to 1 dimensional list of values
     * 
     * @param mixed $data 
     * @param bool $prefix 
     * @return array 
     */
    private static function toList($data, $prefix = false)
    {
        if (null === $data) {
            return [$prefix => $data];
        }
        if (empty($data)) {
            return [$prefix  =>  ''];
        }
        if (!(is_array($data) || is_object($data))) {
            return [];
        }
        $list = [];
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $list[$prefix ? $prefix . '[' . $key . ']' : $key] = $value;
                continue;
            }
            if ($value instanceof \CURLFile) {
                $list[$key] = $value;
                continue;
            }
            $list = array_merge($list, self::toList($value, $prefix ? $prefix . '[' . $key . ']' : $key));
        }
        return $list;
    }
}
