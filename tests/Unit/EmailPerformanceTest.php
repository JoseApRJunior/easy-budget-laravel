<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ⚡ Testa performance de renderização do layout base
     */
    public function test_base_layout_rendering_performance(): void
    {
        // Arrange
        $data = [
            'title'        => 'Teste de Performance - Easy Budget',
            'content'      => '<h1>Título</h1><p>Conteúdo de teste para medição de performance.</p>',
            'supportEmail' => 'suporte@example.com'
        ];

        // Act
        $startTime = microtime( true );
        $view      = view( 'emails.layouts.base', $data );
        $html      = $view->render();
        $endTime   = microtime( true );

        $renderingTime = ( $endTime - $startTime ) * 1000; // Convertendo para milissegundos

        // Assert - Performance deve ser adequada
        $this->assertLessThan( 100, $renderingTime, 'Renderização deve ser menor que 100ms' );
        $this->assertGreaterThan( 0, strlen( $html ), 'HTML deve ser gerado' );
        $this->assertStringContainsString( '<!doctype html>', $html );
    }

    /**
     * ⚡ Testa performance de renderização de componentes
     */
    public function test_components_rendering_performance(): void
    {
        // Arrange
        $components = [
            [ 'emails.components.button', [ 'url' => 'https://example.com', 'text' => 'Teste' ] ],
            [ 'emails.components.panel', [ 'content' => 'Conteúdo do painel' ] ],
            [ 'emails.components.notice', [ 'content' => 'Aviso importante', 'icon' => 'ℹ️' ] ]
        ];

        $totalTime = 0;

        foreach ( $components as [ $component, $data ] ) {
            // Act
            $startTime = microtime( true );
            $view      = view( $component, $data );
            $html      = $view->render();
            $endTime   = microtime( true );

            $renderingTime = ( $endTime - $startTime ) * 1000;
            $totalTime += $renderingTime;

            // Assert - Cada componente deve renderizar rapidamente
            $this->assertLessThan( 50, $renderingTime, "Componente {$component} deve renderizar em menos de 50ms" );
            $this->assertGreaterThan( 0, strlen( $html ), 'Componente deve gerar HTML' );
        }

        // Assert - Tempo total deve ser razoável
        $this->assertLessThan( 150, $totalTime, 'Tempo total de renderização deve ser menor que 150ms' );
    }

    /**
     * ⚡ Testa performance de templates completos
     */
    public function test_full_templates_rendering_performance(): void
    {
        // Arrange
        $templates = [
            [ 'emails.users.welcome', [
                'first_name'       => 'Performance',
                'confirmationLink' => 'https://example.com/confirm?token=abc123'
            ] ],
            [ 'emails.users.verification', [
                'first_name'       => 'Performance',
                'confirmationLink' => 'https://example.com/confirm?token=def456'
            ] ]
        ];

        foreach ( $templates as [ $template, $data ] ) {
            // Act
            $startTime = microtime( true );
            $view      = view( $template, $data );
            $html      = $view->render();
            $endTime   = microtime( true );

            $renderingTime = ( $endTime - $startTime ) * 1000;

            // Assert - Templates completos devem renderizar rapidamente
            $this->assertLessThan( 200, $renderingTime, "Template {$template} deve renderizar em menos de 200ms" );
            $this->assertGreaterThan( 1000, strlen( $html ), 'Template deve gerar HTML completo' );
        }
    }

    /**
     * ⚡ Testa tamanho do HTML gerado (otimização)
     */
    public function test_html_output_size_optimization(): void
    {
        // Arrange
        $data = [
            'first_name'       => 'Tamanho',
            'confirmationLink' => 'https://example.com/confirm?token=abc123'
        ];

        // Act
        $html = view( 'emails.users.welcome', $data )->render();

        // Assert - HTML deve ter tamanho otimizado
        $htmlSize = strlen( $html );
        $this->assertLessThan( 15000, $htmlSize, 'HTML deve ser menor que 15KB' ); // Tamanho razoável para e-mail
        $this->assertGreaterThan( 1000, $htmlSize, 'HTML deve ter conteúdo mínimo' );

        // Verificar que não há código desnecessário
        $this->assertStringNotContainsString( '{{', $html ); // Não deve ter variáveis não processadas
        $this->assertStringNotContainsString( '}}', $html );
        $this->assertStringNotContainsString( '@@', $html ); // Não deve ter diretivas não processadas
    }

    /**
     * ⚡ Testa performance com dados grandes
     */
    public function test_performance_with_large_data(): void
    {
        // Arrange
        $largeContent = str_repeat( '<p>Este é um parágrafo de teste para verificar performance com conteúdo extenso.</p>', 50 );

        $data = [
            'title'   => 'Teste com Dados Grandes',
            'content' => $largeContent,
        ];

        // Act
        $startTime = microtime( true );
        $view      = view( 'emails.layouts.base', $data );
        $html      = $view->render();
        $endTime   = microtime( true );

        $renderingTime = ( $endTime - $startTime ) * 1000;

        // Assert - Deve lidar bem com dados grandes
        $this->assertLessThan( 500, $renderingTime, 'Deve renderizar dados grandes em menos de 500ms' );
        $this->assertStringContainsString( $largeContent, $html );
    }

    /**
     * ⚡ Testa performance de múltiplas renderizações
     */
    public function test_multiple_renderings_performance(): void
    {
        // Arrange
        $data = [
            'first_name'       => 'Multiplo',
            'confirmationLink' => 'https://example.com/confirm'
        ];

        $totalTime  = 0;
        $renderings = 10;

        // Act
        for ( $i = 0; $i < $renderings; $i++ ) {
            $startTime = microtime( true );
            $view      = view( 'emails.users.welcome', $data );
            $html      = $view->render();
            $endTime   = microtime( true );

            $totalTime += ( $endTime - $startTime ) * 1000;

            // Assert - Cada renderização deve ser consistente
            $this->assertGreaterThan( 0, strlen( $html ) );
        }

        $averageTime = $totalTime / $renderings;

        // Assert - Performance consistente
        $this->assertLessThan( 100, $averageTime, 'Tempo médio deve ser menor que 100ms' );
        $this->assertLessThan( 1000, $totalTime, 'Tempo total deve ser menor que 1000ms' );
    }

    /**
     * ⚡ Testa uso de memória durante renderização
     */
    public function test_memory_usage_during_rendering(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste de Memória',
            'content' => '<h1>Título</h1><p>Conteúdo para teste de uso de memória.</p>',
        ];

        // Act
        $initialMemory = memory_get_usage();
        $view          = view( 'emails.layouts.base', $data );
        $html          = $view->render();
        $finalMemory   = memory_get_usage();

        $memoryUsed = $finalMemory - $initialMemory;

        // Assert - Uso de memória deve ser razoável
        $this->assertLessThan( 2 * 1024 * 1024, $memoryUsed, 'Uso de memória deve ser menor que 2MB' );
        $this->assertGreaterThan( 0, strlen( $html ), 'HTML deve ser gerado' );
    }

    /**
     * ⚡ Testa performance de CSS inline vs externo
     */
    public function test_css_inline_performance(): void
    {
        // Arrange
        $data = [
            'title'   => 'Teste CSS Inline',
            'content' => '<p>Conteúdo com estilos inline para teste de performance.</p>',
        ];

        // Act
        $startTime = microtime( true );
        $view      = view( 'emails.layouts.base', $data );
        $html      = $view->render();
        $endTime   = microtime( true );

        $renderingTime = ( $endTime - $startTime ) * 1000;

        // Assert - CSS inline deve ser rápido
        $this->assertLessThan( 150, $renderingTime, 'CSS inline deve renderizar em menos de 150ms' );
        $this->assertStringContainsString( '<style>', $html ); // CSS deve estar inline
        $this->assertStringNotContainsString( '<link ', $html ); // Não deve ter CSS externo
    }

    /**
     * ⚡ Testa performance com caracteres especiais
     */
    public function test_performance_with_special_characters(): void
    {
        // Arrange
        $specialContent = 'José María O\'Connor & Filhos <test@example.com> - Café naïve résumé';
        $data           = [
            'title'   => 'Teste Caracteres Especiais',
            'content' => "<p>{$specialContent}</p>",
        ];

        // Act
        $startTime = microtime( true );
        $view      = view( 'emails.layouts.base', $data );
        $html      = $view->render();
        $endTime   = microtime( true );

        $renderingTime = ( $endTime - $startTime ) * 1000;

        // Assert - Caracteres especiais não devem impactar performance significativamente
        $this->assertLessThan( 100, $renderingTime, 'Caracteres especiais não devem impactar performance' );
        $this->assertStringContainsString( $specialContent, $html );
    }

    /**
     * ⚡ Testa performance de componentes aninhados
     */
    public function test_nested_components_performance(): void
    {
        // Arrange - Template com múltiplos componentes aninhados
        $data = [
            'first_name'       => 'Aninhado',
            'confirmationLink' => 'https://example.com/confirm'
        ];

        // Act
        $startTime = microtime( true );
        $html      = view( 'emails.users.verification', $data )->render();
        $endTime   = microtime( true );

        $renderingTime = ( $endTime - $startTime ) * 1000;

        // Assert - Componentes aninhados devem ser eficientes
        $this->assertLessThan( 250, $renderingTime, 'Componentes aninhados devem renderizar em menos de 250ms' );

        // Verificar que todos os componentes estão presentes
        $this->assertStringContainsString( 'class="btn"', $html );
        $this->assertStringContainsString( 'class="panel"', $html );
        $this->assertStringContainsString( 'class="notice"', $html );
    }

    /**
     * ⚡ Testa cache de views (se aplicável)
     */
    public function test_view_caching_performance(): void
    {
        // Arrange
        $data = [
            'first_name'       => 'Cache',
            'confirmationLink' => 'https://example.com/confirm'
        ];

        // Act - Primeira renderização
        $startTime1 = microtime( true );
        $html1      = view( 'emails.users.welcome', $data )->render();
        $endTime1   = microtime( true );

        // Segunda renderização (deve ser mais rápida se houver cache)
        $startTime2 = microtime( true );
        $html2      = view( 'emails.users.welcome', $data )->render();
        $endTime2   = microtime( true );

        $firstRenderTime  = ( $endTime1 - $startTime1 ) * 1000;
        $secondRenderTime = ( $endTime2 - $startTime2 ) * 1000;

        // Assert - Ambas as renderizações devem ser rápidas
        $this->assertLessThan( 200, $firstRenderTime, 'Primeira renderização deve ser rápida' );
        $this->assertLessThan( 200, $secondRenderTime, 'Segunda renderização deve ser rápida' );

        // HTML deve ser idêntico
        $this->assertEquals( $html1, $html2, 'Múltiplas renderizações devem gerar HTML idêntico' );
    }

}
