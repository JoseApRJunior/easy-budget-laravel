<?php

namespace core\enums;

enum RouteWildcard: string
{
    case numeric = '[0-9]+';
    case alpha = '[a-z]+';
    case any = '[a-zA-Z0-9\-]+';

}
