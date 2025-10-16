<?php

function findFiles( $dir, $pattern, &$results = [] )
{
    $files = scandir( $dir );

    foreach ( $files as $file ) {
        if ( $file === '.' || $file === '..' ) continue;

        $path = $dir . '/' . $file;

        if ( is_dir( $path ) ) {
            findFiles( $path, $pattern, $results );
        } else if ( pathinfo( $path, PATHINFO_EXTENSION ) === 'php' ) {
            $content = file_get_contents( $path );
            if ( strpos( $content, $pattern ) !== false ) {
                $results[] = $path;
            }
        }
    }

    return $results;
}

// Procurar por TestEmailVerificationEventListener
$results = findFiles( 'app', 'TestEmailVerificationEventListener' );

if ( empty( $results ) ) {
    echo "Nenhum arquivo encontrado contendo 'TestEmailVerificationEventListener'\n";
} else {
    echo "Arquivos encontrados:\n";
    foreach ( $results as $file ) {
        echo "- $file\n";
    }
}

// Procurar por $event->token
$results2 = findFiles( '.', '\$event->token' );

if ( empty( $results2 ) ) {
    echo "Nenhum arquivo encontrado contendo '\$event->token'\n";
} else {
    echo "Arquivos encontrados contendo '\$event->token':\n";
    foreach ( $results2 as $file ) {
        echo "- $file\n";
    }
}
