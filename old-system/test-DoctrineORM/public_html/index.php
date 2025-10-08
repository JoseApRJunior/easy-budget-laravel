<?php
// Configurações de charset - coloque isso no topo do arquivo
header( 'Content-Type: text/html; charset=UTF-8' );
mb_internal_encoding( 'UTF-8' );
mb_regex_encoding( 'UTF-8' );
ini_set( 'default_charset', 'UTF-8' );

use core\library\Session;

// Configurar o locale para português
setlocale( LC_ALL, 'pt_BR.UTF-8', 'pt_BR', 'portuguese' );
date_default_timezone_set( 'America/Sao_Paulo' );

// Inclua o arquivo de constantes
require dirname( __DIR__ ) . '/app/helpers/constantes.php';

// Carregue o bootstrap
require PUBLIC_PATH . '/bootstrap.php';

// remove todas as mensagens visualizadas flash
Session::removeViewedFlash();

// Marque todas as mensagens visualizadas flash
Session::markAsViewed();

// Carregue as rotas
require BASE_PATH . '/routes/web.php';

// Ative a exibição de erros para depuração
if ( env( 'APP_ENV' ) === 'development' ) {
    ini_set( 'display_errors', 1 );
    error_reporting( E_ALL );
    ini_set( 'display_startup_errors', 1 );
    ini_set( 'xdebug.overload_var_dump', 0 );
}
