<?php

$logFile = 'storage/logs/laravel.log';

if ( !file_exists( $logFile ) ) {
    echo "Arquivo de log não encontrado: $logFile\n";
    exit( 1 );
}

$content = file_get_contents( $logFile );
$lines   = explode( "\n", $content );

// Procurar pelas últimas 20 linhas que contenham os termos relacionados
$foundLines = [];
foreach ( array_reverse( $lines ) as $line ) {
    if (
        strpos( $line, 'EmailVerificationRequested' ) !== false ||
        strpos( $line, 'SendEmailVerification' ) !== false ||
        strpos( $line, 'email_verification' ) !== false
    ) {
        $foundLines[] = $line;
        if ( count( $foundLines ) >= 20 ) break;
    }
}

if ( empty( $foundLines ) ) {
    echo "Nenhuma ocorrência encontrada nos logs recentes.\n";
} else {
    echo "Ocorrências encontradas (últimas " . count( $foundLines ) . "):\n";
    echo "----------------------------------------\n";
    foreach ( array_reverse( $foundLines ) as $line ) {
        echo $line . "\n";
    }
}
