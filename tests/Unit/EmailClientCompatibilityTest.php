<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailClientCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 📧 Testa compatibilidade com Gmail
     */
    public function test_gmail_compatibility(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Gmail - Easy Budget',
            'content' => '
                <h1>Título do E-mail</h1>
                <p>Este e-mail deve funcionar perfeitamente no Gmail.</p>
                <a href="https://example.com/confirm" class="btn">Confirmar</a>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Compatível com Gmail
        $this->assertStringContainsString( '<!doctype html>', $html ); // Gmail requer DOCTYPE
        $this->assertStringContainsString( '<table', $html ); // Gmail prefere tabelas para layout
        $this->assertStringContainsString( 'cellpadding="0"', $html ); // Gmail requer cellpadding
        $this->assertStringContainsString( 'cellspacing="0"', $html ); // Gmail requer cellspacing
        $this->assertStringContainsString( 'border="0"', $html ); // Gmail requer border
        $this->assertStringNotContainsString( '<div class="email-wrap">', $html ); // Gmail tem problemas com divs flexíveis
    }

    /**
     * 📧 Testa compatibilidade com Outlook
     */
    public function test_outlook_compatibility(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Outlook - Easy Budget',
            'content' => '
                <h1>Título do E-mail</h1>
                <p>Este e-mail deve funcionar perfeitamente no Outlook.</p>
                <a href="https://example.com/confirm" class="btn">Confirmar</a>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Compatível com Outlook
        $this->assertStringContainsString( 'lang="pt-BR"', $html ); // Outlook requer idioma
        $this->assertStringContainsString( 'utf-8', $html ); // Outlook requer encoding UTF-8
        $this->assertStringContainsString( 'width=device-width', $html ); // Outlook requer viewport
        $this->assertStringNotContainsString( 'box-shadow', $html ); // Outlook não suporta box-shadow
        $this->assertStringNotContainsString( 'border-radius', $html ); // Outlook tem suporte limitado a border-radius
    }

    /**
     * 📧 Testa compatibilidade com Apple Mail
     */
    public function test_apple_mail_compatibility(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Apple Mail - Easy Budget',
            'content' => '
                <h1>Título do E-mail</h1>
                <p>Este e-mail deve funcionar perfeitamente no Apple Mail.</p>
                <a href="https://example.com/confirm" class="btn">Confirmar</a>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Compatível com Apple Mail
        $this->assertStringContainsString( '<!doctype html>', $html ); // Apple Mail requer DOCTYPE
        $this->assertStringContainsString( '<html lang="pt-BR">', $html ); // Apple Mail requer idioma
        $this->assertStringContainsString( '<meta charset="utf-8">', $html ); // Apple Mail requer encoding
        $this->assertStringContainsString( '<title>', $html ); // Apple Mail usa título
        $this->assertStringContainsString( 'font-family: Arial, sans-serif', $html ); // Apple Mail funciona bem com Arial
    }

    /**
     * 📧 Testa compatibilidade com Yahoo Mail
     */
    public function test_yahoo_mail_compatibility(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Yahoo Mail - Easy Budget',
            'content' => '
                <h1>Título do E-mail</h1>
                <p>Este e-mail deve funcionar perfeitamente no Yahoo Mail.</p>
                <a href="https://example.com/confirm" class="btn">Confirmar</a>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Compatível com Yahoo Mail
        $this->assertStringContainsString( '<!doctype html>', $html ); // Yahoo requer DOCTYPE
        $this->assertStringContainsString( '<html lang="pt-BR">', $html ); // Yahoo requer idioma
        $this->assertStringContainsString( '<meta charset="utf-8">', $html ); // Yahoo requer encoding
        $this->assertStringContainsString( 'width=device-width', $html ); // Yahoo requer viewport
        $this->assertStringNotContainsString( 'flex', $html ); // Yahoo tem suporte limitado a flexbox
    }

    /**
     * 📧 Testa compatibilidade com clientes móveis
     */
    public function test_mobile_email_clients_compatibility(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Mobile - Easy Budget',
            'content' => '
                <h1>Título do E-mail</h1>
                <p>Este e-mail deve funcionar perfeitamente em clientes móveis.</p>
                <a href="https://example.com/confirm" class="btn">Confirmar</a>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Compatível com clientes móveis
        $this->assertStringContainsString( 'width=device-width', $html ); // Viewport móvel
        $this->assertStringContainsString( 'max-width: 600px', $html ); // Largura máxima para mobile
        $this->assertStringContainsString( '@media (max-width:420px)', $html ); // Media queries para mobile
        $this->assertStringContainsString( 'font-size: 18px', $html ); // Tamanho de fonte adaptado
        $this->assertStringContainsString( 'padding: 16px', $html ); // Padding adaptado para mobile
    }

    /**
     * 📧 Testa uso de tabelas para layout (compatibilidade máxima)
     */
    public function test_table_based_layout_compatibility(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Layout em Tabela - Easy Budget',
            'content' => '
                <h1>Título do E-mail</h1>
                <p>Este e-mail usa tabelas para máxima compatibilidade.</p>
                <a href="https://example.com/confirm" class="btn">Confirmar</a>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Deve usar tabelas para layout quando necessário
        $this->assertStringContainsString( '<table', $html ); // Usa tabelas para estrutura
        $this->assertStringContainsString( 'cellpadding=', $html ); // Tabelas com cellpadding
        $this->assertStringContainsString( 'cellspacing=', $html ); // Tabelas com cellspacing
        $this->assertStringContainsString( 'border=', $html ); // Tabelas com border
        $this->assertStringContainsString( 'width=', $html ); // Larguras definidas
    }

    /**
     * 📧 Testa estilos inline (compatibilidade máxima)
     */
    public function test_inline_styles_compatibility(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Estilos Inline - Easy Budget',
            'content' => '
                <h1>Título do E-mail</h1>
                <p>Este e-mail usa estilos inline para máxima compatibilidade.</p>
                <a href="https://example.com/confirm" class="btn">Confirmar</a>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Deve usar estilos inline
        $this->assertStringContainsString( '<style>', $html ); // CSS inline no cabeçalho
        $this->assertStringContainsString( '</style>', $html );
        $this->assertStringNotContainsString( '<link ', $html ); // Não usa CSS externo
        $this->assertStringNotContainsString( 'background: #', $html ); // Cores definidas
        $this->assertStringContainsString( 'font-family:', $html ); // Fontes definidas
    }

    /**
     * 📧 Testa compatibilidade com clientes webmail
     */
    public function test_webmail_clients_compatibility(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Webmail - Easy Budget',
            'content' => '
                <h1>Título do E-mail</h1>
                <p>Este e-mail deve funcionar perfeitamente em webmails.</p>
                <a href="https://example.com/confirm" class="btn">Confirmar</a>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Compatível com webmails
        $this->assertStringContainsString( '<!doctype html>', $html ); // Webmails requerem DOCTYPE
        $this->assertStringContainsString( '<html lang="pt-BR">', $html ); // Idioma especificado
        $this->assertStringContainsString( '<meta charset="utf-8">', $html ); // Encoding UTF-8
        $this->assertStringContainsString( '<title>', $html ); // Título presente
        $this->assertStringContainsString( 'width=device-width', $html ); // Viewport configurado
    }

    /**
     * 📧 Testa fallback para clientes sem suporte a CSS avançado
     */
    public function test_fallback_for_basic_css_clients(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Fallback - Easy Budget',
            'content' => '
                <h1>Título do E-mail</h1>
                <p>Este e-mail deve funcionar mesmo em clientes com CSS básico.</p>
                <a href="https://example.com/confirm" class="btn">Confirmar</a>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Deve ter fallbacks para CSS básico
        $this->assertStringContainsString( 'font-family: Arial, sans-serif', $html ); // Fonte básica
        $this->assertStringContainsString( 'background: #', $html ); // Cores sólidas
        $this->assertStringContainsString( 'color: #', $html ); // Cores de texto
        $this->assertStringContainsString( 'padding:', $html ); // Espaçamento básico
        $this->assertStringContainsString( 'margin:', $html ); // Espaçamento básico
    }

    /**
     * 📧 Testa compatibilidade com dark mode (clientes que suportam)
     */
    public function test_dark_mode_compatibility(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Dark Mode - Easy Budget',
            'content' => '
                <h1>Título do E-mail</h1>
                <p>Este e-mail deve se adaptar ao dark mode quando suportado.</p>
                <a href="https://example.com/confirm" class="btn">Confirmar</a>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Preparado para dark mode
        $this->assertStringContainsString( 'color: #333', $html ); // Cores que funcionam em ambos os modos
        $this->assertStringContainsString( 'background: #', $html ); // Fundos definidos
        $this->assertStringNotContainsString( 'color: inherit', $html ); // Não depende de herança
        $this->assertStringNotContainsString( 'background: inherit', $html ); // Não depende de herança
    }

    /**
     * 📧 Testa atributos de segurança para diferentes clientes
     */
    public function test_security_attributes_compatibility(): void
    {
        // Arrange
        $data = [
            'url'  => 'https://example.com/confirm',
            'text' => 'Confirmar E-mail'
        ];

        // Act
        $view = view( 'emails.components.button', $data );
        $html = $view->render();

        // Assert - Atributos de segurança compatíveis
        $this->assertStringContainsString( 'target="_blank"', $html ); // Nova aba
        $this->assertStringContainsString( 'rel="noopener noreferrer"', $html ); // Segurança
        $this->assertStringContainsString( 'https://', $html ); // Protocolo seguro
        $this->assertStringNotContainsString( 'javascript:', $html ); // Não usar javascript:
    }

    /**
     * 📧 Testa compatibilidade internacional (caracteres especiais)
     */
    public function test_international_characters_compatibility(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Internacional - Easy Budget',
            'content' => '
                <h1>Título com Caracteres Especiais</h1>
                <p>José María O\'Connor - naïve résumé café</p>
                <a href="https://example.com/confirm" class="btn">Confirmar</a>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Caracteres internacionais devem funcionar
        $this->assertStringContainsString( 'charset="utf-8"', $html ); // Encoding UTF-8
        $this->assertStringContainsString( 'lang="pt-BR"', $html ); // Idioma especificado
        $this->assertStringContainsString( 'José María', $html ); // Caracteres especiais
        $this->assertStringContainsString( 'O\'Connor', $html ); // Apóstrofo
        $this->assertStringContainsString( 'naïve', $html ); // Caracteres com acento
        $this->assertStringContainsString( 'résumé', $html ); // Caracteres com acento
        $this->assertStringContainsString( 'café', $html ); // Caracteres com acento
    }

}
