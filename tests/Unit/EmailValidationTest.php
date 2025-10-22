<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ✅ Testa validação HTML do layout base
     */
    public function test_base_layout_html_validation(): void
    {
        // Arrange
        $data = [
            'title'        => 'E-mail Válido - Easy Budget',
            'content'      => '<h1>Título</h1><p>Conteúdo válido</p>',
            'supportEmail' => 'suporte@example.com'
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - HTML deve ser válido
        $this->assertStringStartsWith( '<!doctype html>', $html );
        $this->assertStringContainsString( '<html lang="pt-BR">', $html );
        $this->assertStringContainsString( '</html>', $html );
        $this->assertStringContainsString( '<head>', $html );
        $this->assertStringContainsString( '</head>', $html );
        $this->assertStringContainsString( '<body>', $html );
        $this->assertStringContainsString( '</body>', $html );
    }

    /**
     * ✅ Testa validação CSS do layout base
     */
    public function test_base_layout_css_validation(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste CSS',
            'content' => '<p>Teste</p>',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - CSS deve ser válido
        $this->assertStringContainsString( '<style>', $html );
        $this->assertStringContainsString( '</style>', $html );

        // Verificar propriedades CSS válidas
        $this->assertStringContainsString( 'font-family:', $html );
        $this->assertStringContainsString( 'background:', $html );
        $this->assertStringContainsString( 'color:', $html );
        $this->assertStringContainsString( 'margin:', $html );
        $this->assertStringContainsString( 'padding:', $html );
        $this->assertStringContainsString( 'border-radius:', $html );
        $this->assertStringContainsString( 'box-shadow:', $html );
    }

    /**
     * ✅ Testa validação de componentes individuais
     */
    public function test_individual_components_validation(): void
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
            'icon'    => 'ℹ️'
        ] )->render();

        // Assert - Cada componente deve gerar HTML válido
        foreach ( [ $buttonHtml, $panelHtml, $noticeHtml ] as $html ) {
            $this->assertStringContainsString( '<', $html ); // Deve ter tags HTML
            $this->assertStringContainsString( '>', $html ); // Deve fechar tags
            $this->assertStringNotContainsString( '<<', $html ); // Não deve ter tags duplas
        }
    }

    /**
     * ✅ Testa ausência de erros de sintaxe CSS
     */
    public function test_css_syntax_validation(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste CSS Syntax',
            'content' => '<p>Teste</p>',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - CSS deve ter sintaxe válida
        $this->assertStringNotContainsString( '{{', $html ); // Não deve ter variáveis não processadas
        $this->assertStringNotContainsString( '}}', $html );
        $this->assertStringNotContainsString( '@@', $html ); // Não deve ter diretivas não processadas
        $this->assertStringNotContainsString( 'section', $html );
        $this->assertStringNotContainsString( 'endsection', $html );
        $this->assertStringNotContainsString( 'extends', $html );
    }

    /**
     * ✅ Testa validação de atributos HTML
     */
    public function test_html_attributes_validation(): void
    {
        // Arrange
        $data = [
            'url'  => 'https://example.com/confirm',
            'text' => 'Confirmar E-mail'
        ];

        // Act
        $view = view( 'emails.components.button', $data );
        $html = $view->render();

        // Assert - Atributos HTML devem ser válidos
        $this->assertStringContainsString( 'href=', $html ); // Atributo href presente
        $this->assertStringContainsString( 'target="_blank"', $html ); // Atributo target válido
        $this->assertStringContainsString( 'rel="noopener noreferrer"', $html ); // Atributo rel válido
        $this->assertStringContainsString( 'class="btn"', $html ); // Atributo class válido
        $this->assertStringNotContainsString( '=" "', $html ); // Não deve ter atributos vazios
    }

    /**
     * ✅ Testa validação de estrutura de templates
     */
    public function test_template_structure_validation(): void
    {
        // Arrange
        $data = [
            'first_name'       => 'Teste',
            'confirmationLink' => 'https://example.com/confirm'
        ];

        // Act
        $welcomeHtml      = view( 'emails.users.welcome', $data )->render();
        $verificationHtml = view( 'emails.users.verification', $data )->render();

        // Assert - Ambos os templates devem ter estrutura válida
        foreach ( [ $welcomeHtml, $verificationHtml ] as $html ) {
            $this->assertStringContainsString( '<!doctype html>', $html );
            $this->assertStringContainsString( '<title>', $html );
            $this->assertStringContainsString( '</title>', $html );
            $this->assertStringContainsString( '<meta charset="utf-8">', $html );
            $this->assertStringContainsString( '<meta name="viewport"', $html );
            $this->assertStringContainsString( '<style>', $html );
            $this->assertStringContainsString( '</style>', $html );
        }
    }

    /**
     * ✅ Testa validação de caracteres especiais
     */
    public function test_special_characters_validation(): void
    {
        // Arrange
        $specialData = [
            'first_name'       => 'José María O\'Connor & Filhos <test@example.com>',
            'confirmationLink' => 'https://example.com/confirm?token=abc&user=123'
        ];

        // Act
        $html = view( 'emails.users.welcome', $specialData )->render();

        // Assert - Caracteres especiais devem ser tratados adequadamente
        $this->assertStringContainsString( 'José María O\'Connor & Filhos', $html );
        $this->assertStringContainsString( 'token=abc&user=123', $html );
        $this->assertStringNotContainsString( '<', $html ); // Não deve escapar caracteres especiais
        $this->assertStringNotContainsString( '>', $html );
        $this->assertStringNotContainsString( '&', $html );
    }

    /**
     * ✅ Testa validação de URLs
     */
    public function test_url_validation(): void
    {
        // Arrange
        $testCases = [
            'https://example.com/confirm',
            'https://dev.easybudget.net.br/verify',
            'https://subdominio.exemplo.com/path?param=value',
            '/relative/path',
            '#anchor'
        ];

        foreach ( $testCases as $url ) {
            // Act
            $html = view( 'emails.components.button', [
                'url'  => $url,
                'text' => 'Teste'
            ] )->render();

            // Assert - URLs devem ser válidas
            $this->assertStringContainsString( $url, $html );
            $this->assertStringContainsString( '<a href=', $html );
        }
    }

    /**
     * ✅ Testa validação de estilos inline
     */
    public function test_inline_styles_validation(): void
    {
        // Arrange
        $data = [
            'url'   => 'https://example.com',
            'text'  => 'Teste',
            'style' => 'background: #ff0000; color: #ffffff; padding: 10px;'
        ];

        // Act
        $view = view( 'emails.components.button', $data );
        $html = $view->render();

        // Assert - Estilos inline devem ser válidos
        $this->assertStringContainsString( 'style=', $html );
        $this->assertStringContainsString( 'background: #ff0000', $html );
        $this->assertStringContainsString( 'color: #ffffff', $html );
        $this->assertStringContainsString( 'padding: 10px', $html );
        $this->assertStringNotContainsString( 'style=";"', $html ); // Não deve ter estilo vazio
    }

    /**
     * ✅ Testa validação de estrutura de tabelas CSS
     */
    public function test_css_table_structure_validation(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste CSS Tables',
            'content' => '<p>Teste</p>',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - CSS deve ter estrutura de tabelas válida
        $this->assertStringContainsString( '{', $html ); // Chaves de abertura
        $this->assertStringContainsString( '}', $html ); // Chaves de fechamento
        $this->assertStringContainsString( ':', $html ); // Dois pontos
        $this->assertStringContainsString( ';', $html ); // Ponto e vírgula

        // Contar chaves para validar balanceamento
        $openBraces  = substr_count( $html, '{' );
        $closeBraces = substr_count( $html, '}' );
        $this->assertEquals( $openBraces, $closeBraces, 'Chaves CSS devem estar balanceadas' );
    }

    /**
     * ✅ Testa validação de media queries
     */
    public function test_media_queries_validation(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Media Queries',
            'content' => '<p>Teste responsivo</p>',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Media queries devem ser válidas
        $this->assertStringContainsString( '@media', $html );
        $this->assertStringContainsString( '(max-width:420px)', $html );
        $this->assertStringContainsString( 'font-size: 18px', $html ); // Regra dentro da media query
        $this->assertStringContainsString( 'padding: 16px', $html ); // Regra dentro da media query
    }

    /**
     * ✅ Testa validação de comentários HTML/CSS
     */
    public function test_comments_validation(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Comentários',
            'content' => '<!-- Comentário HTML --><p>Teste</p>',
        ];

        // Act
        $html = view( 'emails.layouts.base', $data )->render();

        // Assert - Comentários devem ser válidos
        $this->assertStringContainsString( '<!--', $html ); // Comentário HTML
        $this->assertStringContainsString( '-->', $html ); // Fechamento do comentário
        $this->assertStringNotContainsString( '/*', $html ); // Não deve ter comentários CSS no HTML final
        $this->assertStringNotContainsString( '*/', $html );
    }

}
