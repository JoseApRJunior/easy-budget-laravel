<?php

if (!file_exists($appGlobals = APP_PATH . '/twig/globals/twig.php')) {
    throw new Exception("Please create globals inside app/twig/globals/twig.php file. It should return an array of Twig globals.");
}

$coreGlobals = [
    'csrf' => [
        'token' => $_SESSION[ 'csrf_token' ] ?? '',
        'field' => '<input type="hidden" name="csrf_token" value="' . ($_SESSION[ 'csrf_token' ] ?? '') . '">',
    ],
   ];

$includeAppGlobals = require $appGlobals;

if (!is_array($includeAppGlobals)) {
    throw new Exception("Twig file must return an array");
}

return [ ...$includeAppGlobals, ...$coreGlobals ];
