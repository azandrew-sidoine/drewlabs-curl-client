<?php

namespace Drewlabs\Curl\Utils;

use Closure;
use Exception;

class Pipeline
{

    /**
     * 
     * @param callable[] $pipeline 
     * @return Closure 
     */
    public static function create(...$pipeline)
    {
        $assertOperatorType = function ($operator) {
            if (!is_callable($operator)) {
                throw new Exception('Operator function must be a callable instance');
            }
        };
        return function ($request, $next) use ($pipeline, $assertOperatorType) {
            $nextFunc = function ($req, callable $interceptor) {
                return $interceptor($req, function ($req) {
                    return $req;
                });
            };
            $stack = [function ($request) use ($next) {
                return $next($request);
            }];
            if (count($pipeline) === 0) {
                $pipeline = [function ($request, callable $callback) {
                    return $callback($request);
                }];
            }
            foreach (\array_reverse($pipeline) as $func) {
                $previous = array_pop($stack);
                $assertOperatorType($previous);
                array_push($stack, function ($request) use ($func, $previous) {
                    return $func($request, $previous);
                });
            }
            return $nextFunc($request, array_pop($stack));
        };
    }
}
