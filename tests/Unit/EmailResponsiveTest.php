<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailResponsiveTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 📱 Testa responsividade do layout base em dispositivos móveis
     */
    public function test_base_layout_responsive_mobile(): void
    {
        // Arrange
        $data = [
            'title'        => 'Teste Responsivo',
            'content'      => '<p>Conteúdo de teste para responsividade móvel.</p>',
            'supportEmail' => 'suporte@example.com'
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Verificações de responsividade móvel
        $this->assertStringContainsString( 'width=device-width', $html ); // Meta viewport
        $this->assertStringContainsString( 'max-width: 600px', $html ); // Largura máxima
        $this->assertStringContainsString( '@media (max-width:420px)', $html ); // Media query móvel

        // Verificar que elementos se adaptam em mobile
        $this->assertStringContainsString( 'padding: 16px', $html ); // Padding reduzido em mobile
        $this->assertStringContainsString( 'font-size: 18px', $html ); // Título menor em mobile
    }

    /**
     * 📱 Testa responsividade do componente botão em dispositivos móveis
     */
    public function test_button_component_responsive_mobile(): void
    {
        // Arrange
        $data = [
            'url'  => 'https://example.com',
            'text' => 'Botão de Teste Responsivo'
        ];

        // Act
        $view = view( 'emails.components.button', $data );
        $html = $view->render();

        // Assert - Botão deve ser touch-friendly (estilos vêm do layout base)
        $this->assertStringContainsString( 'class="btn"', $html ); // Classe CSS definida
        $this->assertStringContainsString( 'target="_blank"', $html ); // Comportamento adequado
        $this->assertStringContainsString( 'rel="noopener noreferrer"', $html ); // Segurança
    }

    /**
     * 📱 Testa layout em diferentes breakpoints
     */
    public function test_email_layout_breakpoints(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Breakpoints',
            'content' => '<h1>Título</h1><p>Parágrafo de teste</p>',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Deve conter media queries para diferentes dispositivos
        $this->assertStringContainsString( '@media (max-width:420px)', $html );

        // Verificar elementos que se adaptam
        $this->assertStringContainsString( 'font-size: 20px', $html ); // Desktop
        $this->assertStringContainsString( 'font-size: 18px', $html ); // Mobile
    }

    /**
     * 📱 Testa comportamento em dispositivos muito pequenos
     */
    public function test_email_layout_very_small_devices(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Dispositivo Pequeno',
            'content' => '<p>Texto muito longo que deve quebrar adequadamente em dispositivos pequenos para testar responsividade.</p>',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Deve ter configurações para dispositivos pequenos
        $this->assertStringContainsString( 'word-break: break-all', $html ); // Quebra de palavras longas
        $this->assertStringContainsString( 'max-width: 600px', $html ); // Largura máxima controlada
        $this->assertStringContainsString( 'margin: 0 auto', $html ); // Centralização
    }

    /**
     * 📱 Testa componente notice em dispositivos móveis
     */
    public function test_notice_component_mobile_layout(): void
    {
        // Arrange
        $data = [
            'content' => 'Este é um aviso que deve se adaptar bem em dispositivos móveis com texto longo.',
            'icon'    => 'ℹ️'
        ];

        // Act
        $view = view( 'emails.components.notice', $data );
        $html = $view->render();

        // Assert - Notice deve ser legível em mobile (estilos vêm do layout base)
        $this->assertStringContainsString( 'class="notice"', $html ); // Classe CSS definida
        $this->assertStringContainsString( 'class="icon"', $html ); // Ícone presente
        $this->assertStringContainsString( 'ℹ️', $html ); // Emoji informativo
    }

    /**
     * 📱 Testa painel em diferentes tamanhos de tela
     */
    public function test_panel_component_responsive_behavior(): void
    {
        // Arrange
        $data = [
            'content' => 'Este painel deve se adaptar bem a diferentes tamanhos de tela mantendo a legibilidade.'
        ];

        // Act
        $view = view( 'emails.components.panel', $data );
        $html = $view->render();

        // Assert - Painel deve ser responsivo (estilos vêm do layout base)
        $this->assertStringContainsString( 'class="panel"', $html ); // Classe CSS definida
        $this->assertStringContainsString( 'Este painel deve se adaptar', $html ); // Conteúdo presente
    }

    /**
     * 📱 Testa comportamento do e-mail em orientação paisagem mobile
     */
    public function test_email_mobile_landscape_orientation(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Orientação Paisagem',
            'content' => '<p>Este e-mail deve funcionar bem mesmo em orientação paisagem do celular.</p>',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Deve funcionar em qualquer orientação
        $this->assertStringContainsString( 'width=device-width', $html ); // Respeita largura do dispositivo
        $this->assertStringContainsString( 'max-width: 600px', $html ); // Limitação de largura
        $this->assertStringContainsString( 'margin: 0 auto', $html ); // Centralização automática
    }

    /**
     * 📱 Testa compatibilidade com diferentes densidades de pixel
     */
    public function test_email_high_dpi_displays(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Alta Resolução',
            'content' => '<p>E-mail deve ser nítido em telas de alta resolução.</p>',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Deve funcionar bem em telas de alta resolução
        $this->assertStringContainsString( 'border-radius', $html ); // Elementos arredondados
        $this->assertStringContainsString( 'box-shadow', $html ); // Sombras definidas
        $this->assertStringContainsString( 'max-width: 600px', $html ); // Largura máxima definida
    }

}
