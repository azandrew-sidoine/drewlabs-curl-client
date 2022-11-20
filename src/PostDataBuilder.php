<?php

namespace Drewlabs\Curl;

use Drewlabs\TxnClient\Converters\JSONEncoder;
use RuntimeException;

class PostDataBuilder
{

    /**
     * 
     * @var PostData
     */
    private $postData;

    /**
     * 
     * @var \Closure
     */
    private $encoder = false;

    /**
     * Creates a builder instance
     * 
     * @param PostData $data 
     * @return void 
     */
    public function __construct(PostData $data)
    {
        $this->postData = $data;
    }

    /**
     * Prepare the builder to build the data as json string
     * 
     * @return static 
     */
    public function asJSON()
    {
        $this->asJson = function (PostData $data) {
            if (!$data->isJSONSerializable()) {
                throw new RuntimeException('Post data is not JSON serializable');
            }
            return JSONEncoder::new()->encode($data->getContent());
        };
        return $this;
    }

    /**
     * Prepare the builder to build data a raw PHP array
     * 
     * @return static 
     */
    public function asURLEncoded()
    {
        $this->encoder = $this->useDefaultEncoder(true);
        return $this;
    }

    /**
     * Build post data content into a value that can be handled by the curl client
     * 
     * @return string|false|array 
     * @throws RuntimeException 
     */
    public function build()
    {
        return ($this->encoder ?? $this->useDefaultEncoder())->__invoke($this->postData);
    }

    /**
     * Serialize the the post data content as JSON string
     * 
     * @return string|false 
     * 
     * @throws RuntimeException 
     */
    private function useDefaultEncoder($url_encoded = false)
    {
        return function (PostData $postData) use ($url_encoded) {
            [$output, $isBinary] = [$postData->isMultiDimensional() ? $postData->flatten()->getContent() :  $postData->getContent(), false];
            if (is_array($output) || is_object($output)) {
                foreach ($output as $key => $value) {
                    if (is_string($value) && strpos($value, '@') === 0 && is_file(substr($value, 1))) {
                        $value = class_exists('CURLFile') ? new \CURLFile(substr($value, 1)) : (function_exists('curl_file_create') ? curl_file_create($value) : $value);
                    }
                    if ($value instanceof \CURLFile) {
                        $isBinary = true;
                        $output[$key] = $value;
                    }
                }
            }
            if (!$isBinary && (is_array($output) || is_object($output)) && $url_encoded) {
                return implode('&', array_map(function ($key, $value) {
                    // Encode keys and values using urlencode() to match the default
                    // behavior http_build_query() where $encoding_type is
                    // PHP_QUERY_RFC1738.
                    // https://github.com/php/php-src/blob/master/ext/standard/http.c
                    return urlencode(strval($key)) . '=' . urlencode(strval($value));
                }, array_keys((array)$output), array_values((array)$output)));
            }
            return $output;
        };
    }
}
