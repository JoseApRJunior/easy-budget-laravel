<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    public function test_create_view_renders_status_options(): void
    {
        $response = $this->get(route('provider.services.create'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.service.create');

        $html = $response->getContent();
        $this->assertStringContainsString('Selecione um status', $html);

        // Verifica que pelo menos um status conhecido aparece com descrição
        $this->assertStringContainsString('Serviço em elaboração, permite modificações', $html);
    }
}

