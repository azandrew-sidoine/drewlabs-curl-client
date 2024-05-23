<?php

namespace Drewlabs\Curl\Utils;

class JsonResponse
{
    use ResponseTrait;

    /** @var array<string,mixed> */
    private $data;

    /**
     * Creates new json response class instance
     * 
     * @param array $data 
     * @param int $status 
     * @param array $headers 
     * @return void 
     */
    public function __construct(array $data = [], int $status = 200, array $headers = [])
    {
        $this->data = $data;
        $this->status = $status;
        $this->headers = $headers ?? [];
    }

    /**
     * returns the json response body
     * 
     * @return array 
     */
    public function getBody(): array
    {
        return $this->data;
    }



    /**
     * Get the value of a given key or default if no value is found
     * 
     * @param string $name 
     * @param mixed $default 
     * @return mixed 
     */
    public function get(string $name, $default = null)
    {
        $default = (!is_string($default) && is_callable($default) ? $default : function () use ($default) {
            return $default;
        });

        if (false !== strpos($name, '.')) {
            $keys = explode('.', $name);
            $count = count($keys);
            $index = 0;
            $current = $this->data;
            while ($index < $count) {
                if (null === $current) {
                    return call_user_func($default, $name);
                }
                $current = array_key_exists($keys[$index], $current) ? $current[$keys[$index]] : $current[$keys[$index]] ?? null;
                $index += 1;
            }
            return $current ?? call_user_func($default, $name);
        }
        return $this->data[$name] ?? call_user_func($default, $name);
    }
}
