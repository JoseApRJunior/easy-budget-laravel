<?php

namespace http;

use core\enums\RouteWildcard as EnumsRouteWildcard;

class RouteWildcard
{
    private string $wildcardReplacd;
    private array  $params = [];

    public function paramsToArray(string $uri, string $wildcard, array $aliases)
    {
        $explodeUri = explode('/', ltrim($uri, '/'));
        $explodeWildcard = explode('/', ltrim($wildcard, '/'));
        $differenceArrays = array_diff($explodeUri, $explodeWildcard);

        $aliasesIndex = 0;
        foreach ($differenceArrays as $index => $param) {
            if (!$aliases) {
                $this->params[ array_values($explodeUri)[ $index - 1 ] ] = is_numeric($param) ? (int) $param : $param;
            } else {
                $this->params[ $aliases[ $aliasesIndex ] ] = is_numeric($param) ? (int) $param : $param;
                $aliasesIndex++;
            }
        }
    }

    public function replaceWildcardWithPattern(string $uriToReplace)
    {
        $this->wildcardReplacd = $uriToReplace;
        if (str_contains($this->wildcardReplacd, '(:numeric)')) {
            $this->wildcardReplacd = str_replace('(:numeric)', EnumsRouteWildcard::numeric->value, $this->wildcardReplacd);
        }

        if (str_contains($this->wildcardReplacd, '(:alpha)')) {
            $this->wildcardReplacd = str_replace('(:alpha)', EnumsRouteWildcard::alpha->value, $this->wildcardReplacd);
        }

        if (str_contains($this->wildcardReplacd, '(:any)')) {
            $this->wildcardReplacd = str_replace('(:any)', EnumsRouteWildcard::any->value, $this->wildcardReplacd);
        }
    }

    public function uriEqualToPattern(string $currentUri, string $wildcardReplaced)
    {
        $wildcard = str_replace('/', '\/', ltrim($wildcardReplaced, '/'));

        return preg_match("/^{$wildcard}$/", ltrim($currentUri, '/'));
    }

    public function getWildcardReplace()
    {
        return $this->wildcardReplacd;
    }

    public function getParams()
    {
        return $this->params ? [ ...$this->params ] : [];
    }

}
