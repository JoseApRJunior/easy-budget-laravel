<?php

function env(string $index, $default = null)
{
    $value = $_ENV[$index] ?? $_SERVER[$index] ?? $default;
    
    // Se o valor for uma string 'false' ou 'true', converte para boolean
    if (is_string($value)) {
        if (strtolower($value) === 'false') {
            return false;
        }
        if (strtolower($value) === 'true') {
            return true;
        }
    }
    
    return $value;
}
