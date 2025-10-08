<?php

function env( string $index ): string
{
    return $_ENV[ $index ] ?? $_SERVER[ $index ];
}
