<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 📧 Testa integração completa do template welcome
     */
    public function test_welcome_email_full_integration(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'  => $tenant->id,
            'first_name' => 'João',
            'email'      => 'joao@example.com'
        ] );

        $confirmationLink = 'https://example.com/confirm?token=' . Str::random( 64 );

        // Act
        $view = view( 'emails.users.welcome', [
            'first_name'       => $user->first_name,
            'confirmationLink' => $confirmationLink
        ] );
        $html = $view->render();

        // Assert - Template completo deve funcionar
        $this->assertStringContainsString( 'Olá <strong>João</strong>', $html );
        $this->assertStringContainsString( $confirmationLink, $html );
        $this->assertStringContainsString( 'Confirmar minha conta', $html );
        $this->assertStringContainsString( 'Este link expira em 30 minutos', $html );
        $this->assertStringContainsString( 'Este é um e-mail automático', $html );
        $this->assertStringContainsString( 'class="btn"', $html );
        $this->assertStringContainsString( 'class="panel"', $html );
    }

    /**
     * 📧 Testa integração completa do template verification
     */
    public function test_verification_email_full_integration(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'  => $tenant->id,
            'first_name' => 'Maria',
            'email'      => 'maria@example.com'
        ] );

        $confirmationLink = 'https://example.com/confirm?token=' . Str::random( 64 );

        // Act
        $view = view( 'emails.users.verification', [
            'first_name'       => $user->first_name,
            'confirmationLink' => $confirmationLink
        ] );
        $html = $view->render();

        // Assert - Template verification deve funcionar completamente
        $this->assertStringContainsString( 'Olá <strong>Maria</strong>', $html );
        $this->assertStringContainsString( $confirmationLink, $html );
        $this->assertStringContainsString( 'Confirmar minha conta', $html );
        $this->assertStringContainsString( 'Este link expira em 30 minutos', $html );
        $this->assertStringContainsString( 'Link expirado ou não recebido?', $html );
        $this->assertStringContainsString( 'Este é um e-mail automático', $html );
        $this->assertStringContainsString( 'Todos os direitos reservados', $html );
        $this->assertStringContainsString( 'class="btn"', $html );
        $this->assertStringContainsString( 'class="panel"', $html );
        $this->assertStringContainsString( 'class="notice"', $html );
    }

    /**
     * 📧 Testa integração com dados reais do banco de dados
     */
    public function test_email_integration_with_real_database_data(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create( [ 'name' => 'Empresa Teste' ] );
        $user   = User::factory()->create( [
            'tenant_id'  => $tenant->id,
            'first_name' => 'Carlos',
            'email'      => 'carlos@teste.com'
        ] );

        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $tenant->id,
            'token'      => Str::random( 64 ),
            'expires_at' => now()->addMinutes( 30 )
        ] );

        // Act
        $view = view( 'emails.users.verification', [
            'first_name'       => $user->first_name,
            'confirmationLink' => "https://example.com/confirm?token={$token->token}"
        ] );
        $html = $view->render();

        // Assert - Integração com dados reais
        $this->assertStringContainsString( 'Carlos', $html );
        $this->assertStringContainsString( $token->token, $html );
        $this->assertStringContainsString( 'Empresa Teste', $html ); // Nome do tenant no título
    }

    /**
     * 📧 Testa herança correta do layout base
     */
    public function test_layout_inheritance_integration(): void
    {
        // Arrange
        $data = [
            'first_name'       => 'Ana',
            'confirmationLink' => 'https://example.com/confirm'
        ];

        // Act - Testar diferentes templates
        $welcomeHtml      = view( 'emails.users.welcome', $data )->render();
        $verificationHtml = view( 'emails.users.verification', $data )->render();

        // Assert - Ambos devem herdar do layout base
        foreach ( [ $welcomeHtml, $verificationHtml ] as $html ) {
            $this->assertStringContainsString( '<!doctype html>', $html );
            $this->assertStringContainsString( '<html lang="pt-BR">', $html );
            $this->assertStringContainsString( '<head>', $html );
            $this->assertStringContainsString( '<body>', $html );
            $this->assertStringContainsString( '<title>', $html );
            $this->assertStringContainsString( 'Easy Budget', $html );
            $this->assertStringContainsString( '<div class="email-wrap">', $html );
            $this->assertStringContainsString( '<div class="header">', $html );
            $this->assertStringContainsString( '<div class="content">', $html );
            $this->assertStringContainsString( '<div class="footer">', $html );
        }
    }

    /**
     * 📧 Testa componentes integrados no contexto completo
     */
    public function test_components_integration_in_full_context(): void
    {
        // Arrange
        $data = [
            'first_name'       => 'Pedro',
            'confirmationLink' => 'https://example.com/confirm?token=abc123'
        ];

        // Act
        $html = view( 'emails.users.verification', $data )->render();

        // Assert - Todos os componentes devem funcionar juntos
        $this->assertStringContainsString( '<div class="notice">', $html );
        $this->assertStringContainsString( '<span class="icon">', $html );
        $this->assertStringContainsString( '<div class="panel">', $html );
        $this->assertStringContainsString( '<a href=', $html );
        $this->assertStringContainsString( 'class="btn"', $html );

        // Verificar que estilos CSS estão presentes
        $this->assertStringContainsString( '.email-wrap', $html );
        $this->assertStringContainsString( '.header', $html );
        $this->assertStringContainsString( '.content', $html );
        $this->assertStringContainsString( '.footer', $html );
        $this->assertStringContainsString( '.btn', $html );
        $this->assertStringContainsString( '.panel', $html );
        $this->assertStringContainsString( '.notice', $html );
    }

    /**
     * 📧 Testa configuração de e-mail com diferentes cenários
     */
    public function test_email_configuration_scenarios(): void
    {
        // Arrange
        $scenarios = [
            [
                'first_name'       => 'Usuário',
                'confirmationLink' => null, // Link nulo
            ],
            [
                'first_name'       => null, // Nome nulo
                'confirmationLink' => 'https://example.com/confirm',
            ],
            [
                'first_name'       => '', // Nome vazio
                'confirmationLink' => 'https://example.com/confirm',
            ]
        ];

        foreach ( $scenarios as $scenario ) {
            // Act
            $html = view( 'emails.users.welcome', $scenario )->render();

            // Assert - Deve funcionar mesmo com dados nulos/vazios
            $this->assertStringContainsString( 'Olá <strong>', $html );
            $this->assertStringContainsString( 'usuário', $html ); // Fallback padrão
            $this->assertStringContainsString( 'Este é um e-mail automático', $html );
        }
    }

    /**
     * 📧 Testa integração com serviço de e-mail real
     */
    public function test_email_service_integration(): void
    {
        // Arrange
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'  => $tenant->id,
            'first_name' => 'Teste',
            'email'      => 'teste@integration.com'
        ] );

        // Act - Simular envio de e-mail real
        $mailable = new \App\Mail\WelcomeUserMail( $user, $tenant, 'https://example.com/confirm' );
        Mail::to( $user->email )->send( $mailable );

        // Assert - E-mail deve ser enviado sem erros
        Mail::assertSent( \App\Mail\WelcomeUserMail::class, function ( $mail ) use ( $user ) {
            return $mail->hasTo( $user->email );
        } );
    }

    /**
     * 📧 Testa template com dados especiais (caracteres especiais, HTML)
     */
    public function test_email_with_special_characters(): void
    {
        // Arrange
        $specialData = [
            'first_name'       => 'José María O\'Connor',
            'confirmationLink' => 'https://example.com/confirm?token=test&user=123'
        ];

        // Act
        $html = view( 'emails.users.welcome', $specialData )->render();

        // Assert - Caracteres especiais devem ser tratados corretamente
        $this->assertStringContainsString( 'José María O\'Connor', $html );
        $this->assertStringContainsString( 'token=test&user=123', $html );
        $this->assertStringNotContainsString( '<', $html ); // Não deve escapar caracteres especiais
    }

    /**
     * 📧 Testa comportamento com diferentes configurações de aplicação
     */
    public function test_email_with_different_app_configurations(): void
    {
        // Arrange
        $data = [
            'first_name'       => 'Config',
            'confirmationLink' => 'https://example.com/confirm'
        ];

        // Act
        $html = view( 'emails.users.welcome', $data )->render();

        // Assert - Deve usar configurações da aplicação
        $this->assertStringContainsString( config( 'app.name' ), $html );
        $this->assertStringContainsString( config( 'app.url' ), $html );
        $this->assertStringContainsString( '© ' . date( 'Y' ), $html );
    }

    /**
     * 📧 Testa template com conteúdo dinâmico complexo
     */
    public function test_email_with_complex_dynamic_content(): void
    {
        // Arrange
        $complexData = [
            'first_name'       => 'Complex',
            'confirmationLink' => 'https://example.com/confirm',
            'additionalInfo'   => 'Esta é uma informação adicional',
            'features'         => [ 'Feature 1', 'Feature 2', 'Feature 3' ]
        ];

        // Act
        $html = view( 'emails.users.welcome', $complexData )->render();

        // Assert - Deve renderizar conteúdo dinâmico corretamente
        $this->assertStringContainsString( 'Complex', $html );
        $this->assertStringContainsString( 'https://example.com/confirm', $html );
        $this->assertStringContainsString( 'Este é um e-mail automático', $html );
        $this->assertStringContainsString( 'class="btn"', $html );
    }

}
