<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Txn\TMoney\Traits;

trait DecodesResponseHeaders
{

    /**
     * Parse request string headers.
     *
     * @param mixed $data
     *
     * @return array
     */
    private function decodeResponseHeaders(string $data): array
    {
        $data = preg_split('/\r\n/', (string) ($data ?? ''), -1, \PREG_SPLIT_NO_EMPTY);
        $httpHeaders = [];
        $httpHeaders['Request-Line'] = reset($data) ?? '';
        for ($i = 1; $i < \count($data); ++$i) {
            if (str_contains($data[$i], ':')) {
                [$key, $value] = array_map(static function ($item) {
                    return $item ? trim($item) : null;
                }, explode(':', $data[$i], 2));
                $httpHeaders[$key] = $value;
            }
        }
        return $httpHeaders;
    }
}