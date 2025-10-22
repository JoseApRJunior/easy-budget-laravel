<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEBUG: Teste de componentes isolados ===\n";

// Teste componente notice isoladamente
$noticeData = [ 'content' => 'Teste de aviso importante', 'icon' => 'ℹ️' ];
$noticeView = view( 'emails.components.notice', $noticeData );
$noticeHtml = $noticeView->render();
echo "NOTICE COMPONENT OUTPUT:\n";
echo htmlspecialchars( $noticeHtml );
echo "\n\n";

// Teste componente panel isoladamente
$panelData = [ 'content' => 'Este painel contém informações importantes' ];
$panelView = view( 'emails.components.panel', $panelData );
$panelHtml = $panelView->render();
echo "PANEL COMPONENT OUTPUT:\n";
echo htmlspecialchars( $panelHtml );
echo "\n\n";

// Teste componente button isoladamente
$buttonData = [ 'url' => 'https://example.com', 'text' => 'Clique aqui' ];
$buttonView = view( 'emails.components.button', $buttonData );
$buttonHtml = $buttonView->render();
echo "BUTTON COMPONENT OUTPUT:\n";
echo htmlspecialchars( $buttonHtml );
echo "\n\n";

// Teste layout base com conteúdo HTML
$layoutData = [
    'title'   => 'Teste Layout Base',
    'content' => '<p>Parágrafo teste</p><a href="https://example.com">Link teste</a>'
];
$layoutView = view( 'emails.layouts.base', $layoutData );
$layoutHtml = $layoutView->render();
echo "LAYOUT BASE OUTPUT (first 1000 chars):\n";
echo htmlspecialchars( substr( $layoutHtml, 0, 1000 ) );
echo "\n...\n";
