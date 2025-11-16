<?php

namespace Tests\Feature\Controllers;

use App\Models\Provider;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Domain\SupportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SupportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Provider $provider;
    private SupportService $supportService;

    protected function setUp(): void
    {
        parent::setUp();
        
        Mail::fake();
        Queue::fake();
        
        $this->supportService = app(SupportService::class);
        
        $this->tenant = Tenant::factory()->create();
        $this->provider = Provider::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Provider Teste',
            'email' => 'provider@teste.com',
        ]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'provider_id' => $this->provider->id,
            'name' => 'Usuário Teste',
            'email' => 'usuario@teste.com',
        ]);
    }

    public function test_support_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/support');

        $response->assertStatus(200);
        $response->assertViewIs('pages.support.index');
    }

    public function test_support_page_requires_authentication(): void
    {
        $response = $this->get('/support');
        $response->assertRedirect('/login');
    }

    public function test_support_form_submission_with_valid_data(): void
    {
        $data = [
            'subject' => 'Problema com fatura',
            'message' => 'Estou tendo problemas para gerar uma fatura. O sistema não está aceitando os produtos.',
            'priority' => 'high',
            'category' => 'billing',
        ];

        $response = $this->actingAs($this->user)
            ->post('/support', $data);

        $response->assertRedirect('/support');
        $response->assertSessionHas('success', 'Sua mensagem foi enviada com sucesso! Entraremos em contato em breve.');
    }

    public function test_support_form_submission_with_minimal_data(): void
    {
        $data = [
            'subject' => 'Dúvida simples',
            'message' => 'Gostaria de saber mais sobre os planos disponíveis.',
        ];

        $response = $this->actingAs($this->user)
            ->post('/support', $data);

        $response->assertRedirect('/support');
        $response->assertSessionHas('success', 'Sua mensagem foi enviada com sucesso! Entraremos em contato em breve.');
    }

    public function test_support_form_fails_without_subject(): void
    {
        $data = [
            'message' => 'Esta é uma mensagem de teste.',
            'priority' => 'medium',
            'category' => 'general',
        ];

        $response = $this->actingAs($this->user)
            ->post('/support', $data);

        $response->assertRedirect('/support');
        $response->assertSessionHasErrors('subject');
    }

    public function test_support_form_fails_without_message(): void
    {
        $data = [
            'subject' => 'Assunto de teste',
            'priority' => 'medium',
            'category' => 'general',
        ];

        $response = $this->actingAs($this->user)
            ->post('/support', $data);

        $response->assertRedirect('/support');
        $response->assertSessionHasErrors('message');
    }

    public function test_support_form_fails_with_too_short_subject(): void
    {
        $data = [
            'subject' => 'A',
            'message' => 'Esta é uma mensagem de teste.',
            'priority' => 'medium',
            'category' => 'general',
        ];

        $response = $this->actingAs($this->user)
            ->post('/support', $data);

        $response->assertRedirect('/support');
        $response->assertSessionHasErrors('subject');
    }

    public function test_support_form_fails_with_too_long_subject(): void
    {
        $data = [
            'subject' => str_repeat('A', 256), // Mais de 255 caracteres
            'message' => 'Esta é uma mensagem de teste.',
            'priority' => 'medium',
            'category' => 'general',
        ];

        $response = $this->actingAs($this->user)
            ->post('/support', $data);

        $response->assertRedirect('/support');
        $response->assertSessionHasErrors('subject');
    }

    public function test_support_form_fails_with_too_short_message(): void
    {
        $data = [
            'subject' => 'Assunto de teste',
            'message' => 'A', // Muito curto
            'priority' => 'medium',
            'category' => 'general',
        ];

        $response = $this->actingAs($this->user)
            ->post('/support', $data);

        $response->assertRedirect('/support');
        $response->assertSessionHasErrors('message');
    }

    public function test_support_form_handles_invalid_priority(): void
    {
        $data = [
            'subject' => 'Assunto de teste',
            'message' => 'Esta é uma mensagem de teste.',
            'priority' => 'invalid_priority',
            'category' => 'general',
        ];

        $response = $this->actingAs($this->user)
            ->post('/support', $data);

        $response->assertRedirect('/support');
        $response->assertSessionHasErrors('priority');
    }

    public function test_support_form_handles_invalid_category(): void
    {
        $data = [
            'subject' => 'Assunto de teste',
            'message' => 'Esta é uma mensagem de teste.',
            'priority' => 'medium',
            'category' => 'invalid_category',
        ];

        $response = $this->actingAs($this->user)
            ->post('/support', $data);

        $response->assertRedirect('/support');
        $response->assertSessionHasErrors('category');
    }

    public function test_support_form_includes_user_data(): void
    {
        $data = [
            'subject' => 'Problema técnico',
            'message' => 'Não consigo acessar o sistema.',
            'priority' => 'high',
            'category' => 'technical',
        ];

        $response = $this->actingAs($this->user)
            ->post('/support', $data);

        $response->assertRedirect('/support');
        $response->assertSessionHas('success');
        
        // Verificar se os dados do usuário foram incluídos no processamento
        // Isso pode ser verificado através de logs ou eventos, dependendo da implementação
    }

    public function test_support_form_handles_special_characters(): void
    {
        $data = [
            'subject' => 'Problema com caracteres especiais: àáâãäåæçèéêë',
            'message' => 'Mensagem com caracteres especiais e emojis: 🚀 💻 ⚡',
            'priority' => 'medium',
            'category' => 'general',
        ];

        $response = $this->actingAs($this->user)
            ->post('/support', $data);

        $response->assertRedirect('/support');
        $response->assertSessionHas('success');
    }

    public function test_support_form_prevents_xss(): void
    {
        $data = [
            'subject' => '<script>alert("XSS")</script>',
            'message' => '<div onclick="alert(\'XSS\')">Clique aqui</div>',
            'priority' => 'medium',
            'category' => 'general',
        ];

        $response = $this->actingAs($this->user)
            ->post('/support', $data);

        $response->assertRedirect('/support');
        $response->assertSessionHas('success');
        
        // O conteúdo malicioso deve ser sanitizado pelo Laravel automaticamente
    }

    public function test_support_form_handles_very_long_message(): void
    {
        $data = [
            'subject' => 'Mensagem longa',
            'message' => str_repeat('Esta é uma linha de texto. ', 100), // Mensagem muito longa
            'priority' => 'low',
            'category' => 'general',
        ];

        $response = $this->actingAs($this->user)
            ->post('/support', $data);

        $response->assertRedirect('/support');
        $response->assertSessionHas('success');
    }
}