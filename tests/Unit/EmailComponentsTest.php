<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use Tests\TestCase;

class EmailComponentsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 📧 Testa o componente de botão reutilizável
     */
    public function test_button_component_renders_correctly(): void
    {
        // Arrange
        $data = [
            'url'   => 'https://example.com/confirm',
            'text'  => 'Confirmar E-mail',
            'style' => 'background: #ff0000;'
        ];

        // Act
        $view = view( 'emails.components.button', $data );
        $html = $view->render();

        // Assert
        $this->assertStringContainsString( 'https://example.com/confirm', $html );
        $this->assertStringContainsString( 'Confirmar E-mail', $html );
        $this->assertStringContainsString( 'target="_blank"', $html );
        $this->assertStringContainsString( 'rel="noopener noreferrer"', $html );
        $this->assertStringContainsString( 'background: #ff0000;', $html );
        $this->assertStringContainsString( 'class="btn"', $html );
    }

    /**
     * 📧 Testa o componente de botão com valores padrão
     */
    public function test_button_component_with_default_values(): void
    {
        // Arrange
        $data = [];

        // Act
        $view = view( 'emails.components.button', $data );
        $html = $view->render();

        // Assert
        $this->assertStringContainsString( '#', $html );
        $this->assertStringContainsString( 'Clique aqui', $html );
        $this->assertStringContainsString( 'target="_blank"', $html );
        $this->assertStringContainsString( 'rel="noopener noreferrer"', $html );
    }

    /**
     * 📧 Testa o componente de painel informativo
     */
    public function test_panel_component_renders_correctly(): void
    {
        // Arrange
        $data = [
            'content' => 'Este é um e-mail automático, por favor não responda.'
        ];

        // Act
        $view = view( 'emails.components.panel', $data );
        $html = $view->render();

        // Assert
        $this->assertStringContainsString( 'Este é um e-mail automático', $html );
        $this->assertStringContainsString( 'class="panel"', $html );
    }

    /**
     * 📧 Testa o componente de painel com valores padrão
     */
    public function test_panel_component_with_default_content(): void
    {
        // Arrange
        $data = [];

        // Act
        $view = view( 'emails.components.panel', $data );
        $html = $view->render();

        // Assert
        $this->assertStringContainsString( 'Este é um e-mail automático', $html );
        $this->assertStringContainsString( 'class="panel"', $html );
    }

    /**
     * 📧 Testa o componente de notice/aviso
     */
    public function test_notice_component_renders_correctly(): void
    {
        // Arrange
        $data = [
            'content' => 'Link expirado ou não recebido?',
            'icon'    => 'ℹ️'
        ];

        // Act
        $view = view( 'emails.components.notice', $data );
        $html = $view->render();

        // Assert
        $this->assertStringContainsString( 'Link expirado ou não recebido?', $html );
        $this->assertStringContainsString( 'ℹ️', $html );
        $this->assertStringContainsString( 'class="notice"', $html );
        $this->assertStringContainsString( 'class="icon"', $html );
    }

    /**
     * 📧 Testa o componente de notice com valores padrão
     */
    public function test_notice_component_with_default_values(): void
    {
        // Arrange
        $data = [];

        // Act
        $view = view( 'emails.components.notice', $data );
        $html = $view->render();

        // Assert
        $this->assertStringContainsString( 'ℹ', $html );
        $this->assertStringContainsString( 'class="notice"', $html );
        $this->assertStringContainsString( 'class="icon"', $html );
    }

    /**
     * 📧 Testa acessibilidade do componente botão
     */
    public function test_button_component_accessibility(): void
    {
        // Arrange
        $data = [
            'url'  => 'https://example.com/confirm',
            'text' => 'Confirmar E-mail'
        ];

        // Act
        $view = view( 'emails.components.button', $data );
        $html = $view->render();

        // Assert - Verificações de acessibilidade
        $this->assertStringNotContainsString( 'javascript:', $html ); // Não deve ter javascript:
        $this->assertStringContainsString( 'https://', $html ); // Deve ser HTTPS
        $this->assertStringContainsString( 'target="_blank"', $html ); // Deve abrir em nova aba
        $this->assertStringContainsString( 'rel="noopener noreferrer"', $html ); // Segurança
    }

    /**
     * 📧 Testa estrutura HTML válida dos componentes
     */
    public function test_components_generate_valid_html(): void
    {
        // Arrange & Act
        $buttonHtml = view( 'emails.components.button', [
            'url'  => 'https://example.com',
            'text' => 'Teste'
        ] )->render();

        $panelHtml = view( 'emails.components.panel', [
            'content' => 'Teste de painel'
        ] )->render();

        $noticeHtml = view( 'emails.components.notice', [
            'content' => 'Teste de notice',
            'icon'    => '⚠️'
        ] )->render();

        // Assert - HTML deve ser válido e bem formado
        $this->assertStringStartsWith( '<a ', trim( $buttonHtml ) );
        $this->assertStringEndsWith( '</a>', trim( $buttonHtml ) );

        $this->assertStringStartsWith( '<div ', trim( $panelHtml ) );
        $this->assertStringEndsWith( '</div>', trim( $panelHtml ) );

        $this->assertStringStartsWith( '<div ', trim( $noticeHtml ) );
        $this->assertStringEndsWith( '</div>', trim( $noticeHtml ) );
    }

    /**
     * 📧 Testa sanitização de conteúdo malicioso
     */
    public function test_components_sanitize_malicious_content(): void
    {
        // Arrange
        $maliciousContent = '<script>alert("xss")</script>Texto normal';

        // Act
        $panelHtml = view( 'emails.components.panel', [
            'content' => $maliciousContent
        ] )->render();

        // Assert - Scripts devem ser removidos ou escapados
        $this->assertStringNotContainsString( '<script>', $panelHtml );
        $this->assertStringContainsString( 'Texto normal', $panelHtml );
    }

}
