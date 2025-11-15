<?php
$file    = 'app/Http/Controllers/ServiceController.php';
$content = file_get_contents( $file );
$content = str_replace( 'services.', 'service.', $content );
file_put_contents( $file, $content );
echo "Substituições realizadas com sucesso!\n";
?>
