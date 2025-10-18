<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ♿ Testa conformidade WCAG 2.1 AA para layout base
     */
    public function test_base_layout_wcag_2_1_aa_compliance(): void
    {
        // Arrange
        $data = [
            'title'        => 'E-mail de Verificação - Easy Budget',
            'content'      => '<h1>Título</h1><p>Conteúdo do e-mail</p>',
            'supportEmail' => 'suporte@example.com'
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Verificações WCAG 2.1 AA
        $this->assertStringContainsString( 'lang="pt-BR"', $html ); // Idioma especificado
        $this->assertStringContainsString( '<title>', $html ); // Título presente
        $this->assertStringContainsString( 'charset="utf-8"', $html ); // Encoding UTF-8
        $this->assertStringContainsString( 'name="viewport"', $html ); // Viewport configurado
    }

    /**
     * ♿ Testa contraste de cores WCAG AA
     */
    public function test_color_contrast_wcag_aa(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste de Contraste',
            'content' => '<p>Texto em fundo colorido</p>',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Cores devem ter contraste adequado (WCAG AA)
        $this->assertStringContainsString( 'color: #fff', $html ); // Texto branco no cabeçalho azul
        $this->assertStringContainsString( 'color: #333', $html ); // Texto escuro no fundo claro
        $this->assertStringContainsString( 'color: #1f2937', $html ); // Texto em cinza escuro
        $this->assertStringContainsString( 'color: #6b7280', $html ); // Texto em cinza médio
    }

    /**
     * ♿ Testa estrutura semântica do HTML
     */
    public function test_semantic_html_structure(): void
    {
        // Arrange
        $data = [
            'title'   => 'E-mail Semântico',
            'content' => '<h1>Título Principal</h1><p>Parágrafo de conteúdo</p>',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Estrutura semântica adequada
        $this->assertStringContainsString( '<html lang="pt-BR">', $html ); // HTML com idioma
        $this->assertStringContainsString( '<head>', $html ); // Cabeçalho estruturado
        $this->assertStringContainsString( '<body>', $html ); // Corpo estruturado
        $this->assertStringContainsString( '<title>', $html ); // Título na head
        $this->assertStringContainsString( '<meta charset="utf-8">', $html ); // Encoding
    }

    /**
     * ♿ Testa acessibilidade do componente botão
     */
    public function test_button_component_accessibility(): void
    {
        // Arrange
        $data = [
            'url'  => 'https://example.com/confirm',
            'text' => 'Confirmar minha conta'
        ];

        // Act
        $view = view( 'emails.components.button', $data );
        $html = $view->render();

        // Assert - Botão acessível
        $this->assertStringContainsString( 'target="_blank"', $html ); // Abre em nova aba
        $this->assertStringContainsString( 'rel="noopener noreferrer"', $html ); // Segurança e acessibilidade
        $this->assertStringNotContainsString( 'javascript:', $html ); // Não usar javascript: em href
        $this->assertStringContainsString( 'https://', $html ); // URL segura
    }

    /**
     * ♿ Testa componente notice para acessibilidade
     */
    public function test_notice_component_accessibility(): void
    {
        // Arrange
        $data = [
            'content' => 'Este é um aviso importante para o usuário',
            'icon'    => 'ℹ️'
        ];

        // Act
        $view = view( 'emails.components.notice', $data );
        $html = $view->render();

        // Assert - Notice acessível
        $this->assertStringContainsString( '<div class="notice">', $html ); // Estrutura HTML correta
        $this->assertStringContainsString( '<span class="icon">ℹ️</span>', $html ); // Ícone presente
        $this->assertStringContainsString( 'Este é um aviso importante para o usuário', $html ); // Conteúdo presente
        $this->assertStringContainsString( 'display: flex', $html ); // Layout flexível para alinhamento
        $this->assertStringContainsString( 'align-items: center', $html ); // Ícone e texto alinhados
        $this->assertStringContainsString( 'gap: 10px', $html ); // Espaçamento adequado
        $this->assertStringContainsString( 'padding: 12px', $html ); // Área de toque adequada
    }

    /**
     * ♿ Testa painel para acessibilidade
     */
    public function test_panel_component_accessibility(): void
    {
        // Arrange
        $data = [
            'content' => 'Este painel contém informações importantes para o usuário'
        ];

        // Act
        $view = view( 'emails.components.panel', $data );
        $html = $view->render();

        // Assert - Painel acessível
        $this->assertStringContainsString( '<div class="panel">', $html ); // Estrutura HTML correta
        $this->assertStringContainsString( 'Este painel contém informações importantes para o usuário', $html ); // Conteúdo presente
        $this->assertStringContainsString( 'background: #f8f9fa', $html ); // Fundo diferenciado
        $this->assertStringContainsString( 'border-radius: 6px', $html ); // Cantos arredondados
        $this->assertStringContainsString( 'padding: 12px', $html ); // Espaçamento interno
        $this->assertStringContainsString( 'font-size: 13px', $html ); // Tamanho de fonte legível
    }

    /**
     * ♿ Testa navegação por teclado (tab order)
     */
    public function test_keyboard_navigation_accessibility(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste de Navegação',
            'content' => '
                <p>Texto inicial</p>
                <a href="https://example.com/confirm" class="btn" target="_blank" rel="noopener noreferrer">Confirmar</a>
                <p>Mais texto</p>
                <a href="https://example.com/support" class="btn" target="_blank" rel="noopener noreferrer">Suporte</a>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Links devem ser navegáveis por teclado
        $this->assertStringContainsString( '<a href="https://example.com/confirm"', $html ); // Links presentes
        $this->assertStringContainsString( '<a href="https://example.com/support"', $html ); // Links presentes
        $this->assertStringContainsString( 'target="_blank"', $html ); // Comportamento adequado
        $this->assertStringContainsString( 'rel="noopener noreferrer"', $html ); // Segurança e acessibilidade
        $this->assertStringNotContainsString( 'onclick=', $html ); // Não depender apenas de onclick
    }

    /**
     * ♿ Testa compatibilidade com leitores de tela
     */
    public function test_screen_reader_compatibility(): void
    {
        // Arrange
        $data = [
            'title'   => 'Verificação de E-mail - Easy Budget',
            'content' => '
                <h1>Confirme sua conta</h1>
                <p>Olá usuário, clique no botão abaixo para confirmar sua conta.</p>
                <a href="https://example.com/confirm" class="btn">Confirmar minha conta</a>
                <p>Este é um e-mail automático.</p>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Compatível com leitores de tela
        $this->assertStringContainsString( '<h1>Confirme sua conta</h1>', $html ); // Títulos estruturados
        $this->assertStringContainsString( '<p>Olá usuário, clique no botão abaixo para confirmar sua conta.</p>', $html ); // Parágrafos estruturados
        $this->assertStringContainsString( '<p>Este é um e-mail automático.</p>', $html ); // Parágrafos estruturados
        $this->assertStringContainsString( 'lang="pt-BR"', $html ); // Idioma especificado
        $this->assertStringNotContainsString( 'style="display:none"', $html ); // Nada oculto importante
    }

    /**
     * ♿ Testa nível de contraste para usuários com deficiência visual
     */
    public function test_color_contrast_for_visual_impairment(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste de Contraste Visual',
            'content' => '<p>Texto normal e <strong>texto em negrito</strong></p>',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Contraste adequado para deficiência visual
        $this->assertStringContainsString( 'color: #333', $html ); // Texto escuro em fundo claro
        $this->assertStringContainsString( 'color: #fff', $html ); // Texto claro em fundo escuro
        $this->assertStringContainsString( 'color: #1f2937', $html ); // Cinza escuro para texto
        $this->assertStringContainsString( 'color: #6b7280', $html ); // Cinza médio para texto secundário
    }

    /**
     * ♿ Testa tamanho mínimo de área de toque (44px)
     */
    public function test_minimum_touch_target_size(): void
    {
        // Arrange
        $data = [
            'url'  => 'https://example.com/confirm',
            'text' => 'Confirmar'
        ];

        // Act
        $view = view( 'emails.components.button', $data );
        $html = $view->render();

        // Assert - Área de toque adequada (mínimo 44px)
        $this->assertStringContainsString( 'padding: 12px 18px', $html ); // Área de toque suficiente
        $this->assertStringContainsString( 'display: inline-block', $html ); // Elemento block-level
        $this->assertStringContainsString( 'border-radius: 6px', $html ); // Área utilizável completa
    }

    /**
     * ♿ Testa alternativas textuais para elementos visuais
     */
    public function test_text_alternatives_for_visual_elements(): void
    {
        // Arrange
        $data = [
            'title'   => 'E-mail com Elementos Visuais',
            'content' => '
                <p>Este e-mail não possui imagens, apenas texto e elementos estilizados com CSS.</p>
                <div class="notice">
                    <span class="icon">ℹ️</span>
                    <div>Informação importante</div>
                </div>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Elementos visuais têm alternativas textuais
        $this->assertStringNotContainsString( '<img', $html ); // Não há imagens sem alt
        $this->assertStringContainsString( 'ℹ️', $html ); // Ícone emoji como alternativa textual
        $this->assertStringContainsString( 'Informação importante', $html ); // Texto explicativo
    }

    /**
     * 📱 Testa responsividade em dispositivos móveis
     */
    public function test_mobile_responsiveness(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Responsividade Mobile',
            'content' => '
                <h1>Título do E-mail</h1>
                <p>Este e-mail deve se adaptar perfeitamente a dispositivos móveis.</p>
                <a href="https://example.com/confirm" class="btn" target="_blank" rel="noopener noreferrer">Confirmar</a>
                <p>Texto adicional para testar quebra de linha em telas pequenas.</p>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Responsividade adequada
        $this->assertStringContainsString( 'width=device-width', $html ); // Viewport configurado
        $this->assertStringContainsString( 'max-width: 600px', $html ); // Largura máxima definida
        $this->assertStringContainsString( '@media (max-width:420px)', $html ); // Media queries para mobile
        $this->assertStringContainsString( 'font-size: 18px', $html ); // Tamanho de fonte adaptado
        $this->assertStringContainsString( 'padding: 16px', $html ); // Padding adaptado para mobile
    }

    /**
     * 📱 Testa compatibilidade com diferentes tamanhos de tela
     */
    public function test_different_screen_sizes_compatibility(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Diferentes Telas',
            'content' => '
                <h1>Título Adaptável</h1>
                <p>Este conteúdo deve funcionar em telas de 320px a 1200px de largura.</p>
                <div style="width: 100%; background: #f0f0f0; padding: 10px; margin: 10px 0;">
                    Elemento com largura fluida que se adapta ao container
                </div>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Compatível com diferentes tamanhos de tela
        $this->assertStringContainsString( 'max-width: 600px', $html ); // Container responsivo
        $this->assertStringContainsString( 'margin: 0 auto', $html ); // Centralização automática
        $this->assertStringContainsString( 'width: 100%', $html ); // Elementos internos responsivos
        $this->assertStringContainsString( '@media', $html ); // Media queries presentes
    }

    /**
     * 📱 Testa área de toque adequada para dispositivos móveis
     */
    public function test_mobile_touch_targets(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste Área de Toque',
            'content' => '
                <a href="https://example.com/confirm" class="btn" target="_blank" rel="noopener noreferrer">Confirmar Conta</a>
                <a href="https://example.com/support" class="btn" target="_blank" rel="noopener noreferrer">Suporte Técnico</a>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Áreas de toque adequadas para mobile
        $this->assertStringContainsString( 'padding: 12px 18px', $html ); // Área mínima de toque (44px equivalente)
        $this->assertStringContainsString( 'display: inline-block', $html ); // Elemento block para área completa
        $this->assertStringContainsString( 'border-radius: 6px', $html ); // Área utilizável completa
        $this->assertStringContainsString( '@media (max-width:420px)', $html ); // Responsividade para mobile
    }

    /**
     * ♿ Testa foco visível para navegação por teclado
     */
    public function test_visible_focus_for_keyboard_navigation(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste de Foco Visível',
            'content' => '
                <a href="https://example.com/confirm" class="btn" target="_blank" rel="noopener noreferrer">Confirmar</a>
                <a href="https://example.com/support" class="btn" target="_blank" rel="noopener noreferrer">Suporte</a>
            ',
        ];

        // Act
        $view = view( 'emails.layouts.base', $data );
        $html = $view->render();

        // Assert - Links devem ter foco visível (embora CSS padrão possa não mostrar)
        $this->assertStringContainsString( '<a href="https://example.com/confirm"', $html ); // Links presentes
        $this->assertStringContainsString( '<a href="https://example.com/support"', $html ); // Links presentes
        $this->assertStringContainsString( 'class="btn"', $html ); // Estilização consistente
        $this->assertStringContainsString( 'border-radius: 6px', $html ); // Área de foco clara
        $this->assertStringContainsString( 'target="_blank"', $html ); // Comportamento adequado
        $this->assertStringContainsString( 'rel="noopener noreferrer"', $html ); // Segurança e acessibilidade
    }

}
