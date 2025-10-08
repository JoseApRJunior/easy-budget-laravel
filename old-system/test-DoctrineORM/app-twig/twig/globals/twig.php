<?php

// app/twig/globals/twig.php
return [ 
    'translations' => function () {
        $jsonFile    = BASE_PATH . '/translations/actions/actions.json';
        $jsonContent = file_get_contents( $jsonFile );

        if ( $jsonContent === false ) {
            return [];
        }

        return json_decode( $jsonContent, true ) ?? [];
    },
];