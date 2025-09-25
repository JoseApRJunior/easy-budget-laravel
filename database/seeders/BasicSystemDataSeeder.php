<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BasicSystemDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Áreas de Atividade
        $areasOfActivity = [
            ['slug' => 'agricultura', 'name' => 'Agricultura, Pecuária e Serviços Relacionados', 'is_active' => true],
            ['slug' => 'extracao', 'name' => 'Extração de Minerais', 'is_active' => true],
            ['slug' => 'industria-transformacao', 'name' => 'Indústrias de Transformação', 'is_active' => true],
            ['slug' => 'energia', 'name' => 'Eletricidade e Gás', 'is_active' => true],
            ['slug' => 'agua-esgoto', 'name' => 'Água, Esgoto, Atividades de Gestão de Resíduos', 'is_active' => true],
            ['slug' => 'construcao', 'name' => 'Construção', 'is_active' => true],
            ['slug' => 'comercio', 'name' => 'Comércio; Reparação de Veículos Automotores e Motocicletas', 'is_active' => true],
            ['slug' => 'transporte', 'name' => 'Transporte, Armazenagem e Correio', 'is_active' => true],
            ['slug' => 'alojamento', 'name' => 'Alojamento e Alimentação', 'is_active' => true],
            ['slug' => 'informacao', 'name' => 'Informação e Comunicação', 'is_active' => true],
            ['slug' => 'financeiro', 'name' => 'Atividades Financeiras, de Seguros e Serviços Relacionados', 'is_active' => true],
            ['slug' => 'imobiliario', 'name' => 'Atividades Imobiliárias', 'is_active' => true],
            ['slug' => 'profissionais', 'name' => 'Atividades Profissionais, Científicas e Técnicas', 'is_active' => true],
            ['slug' => 'administrativo', 'name' => 'Atividades Administrativas e Serviços Complementares', 'is_active' => true],
            ['slug' => 'educacao', 'name' => 'Educação', 'is_active' => true],
            ['slug' => 'saude', 'name' => 'Saúde Humana e Serviços Sociais', 'is_active' => true],
            ['slug' => 'arte', 'name' => 'Artes, Cultura, Esporte e Recreação', 'is_active' => true],
            ['slug' => 'outros-servicos', 'name' => 'Outras Atividades de Serviços', 'is_active' => true],
        ];

        // Usar o nome correto da tabela conforme migration existente
        if (DB::getSchemaBuilder()->hasTable('area_of_activities')) {
            foreach ($areasOfActivity as $area) {
                DB::table('area_of_activities')->updateOrInsert(
                    ['slug' => $area['slug']],
                    $area
                );
            }
        }

        // Profissões
        $professions = [
            ['slug' => 'engenheiro-novo', 'name' => 'Engenheiro', 'is_active' => true],
            ['slug' => 'arquiteto-novo', 'name' => 'Arquiteto', 'is_active' => true],
            ['slug' => 'medico-novo', 'name' => 'Médico', 'is_active' => true],
            ['slug' => 'advogado-novo', 'name' => 'Advogado', 'is_active' => true],
            ['slug' => 'contador-novo', 'name' => 'Contador', 'is_active' => true],
            ['slug' => 'desenvolvedor-novo', 'name' => 'Desenvolvedor de Software', 'is_active' => true],
            ['slug' => 'designer-novo', 'name' => 'Designer', 'is_active' => true],
            ['slug' => 'consultor-novo', 'name' => 'Consultor', 'is_active' => true],
            ['slug' => 'professor-novo', 'name' => 'Professor', 'is_active' => true],
            ['slug' => 'empresario-novo', 'name' => 'Empresário', 'is_active' => true],
            ['slug' => 'autonomo-novo', 'name' => 'Autônomo', 'is_active' => true],
            ['slug' => 'freelancer-novo', 'name' => 'Freelancer', 'is_active' => true],
        ];

        foreach ($professions as $profession) {
            DB::table('professions')->updateOrInsert(
                ['slug' => $profession['slug']],
                $profession
            );
        }

        // Units
        $units = [
            ['name' => 'Unidade', 'symbol' => 'un', 'type' => 'unidade', 'is_active' => true],
            ['name' => 'Quilograma', 'symbol' => 'kg', 'type' => 'peso', 'is_active' => true],
            ['name' => 'Metro', 'symbol' => 'm', 'type' => 'comprimento', 'is_active' => true],
            ['name' => 'Metro Quadrado', 'symbol' => 'm²', 'type' => 'area', 'is_active' => true],
            ['name' => 'Metro Cúbico', 'symbol' => 'm³', 'type' => 'volume', 'is_active' => true],
            ['name' => 'Litro', 'symbol' => 'L', 'type' => 'volume', 'is_active' => true],
            ['name' => 'Hora', 'symbol' => 'h', 'type' => 'tempo', 'is_active' => true],
            ['name' => 'Dia', 'symbol' => 'dia', 'type' => 'tempo', 'is_active' => true],
            ['name' => 'Mês', 'symbol' => 'mês', 'type' => 'tempo', 'is_active' => true],
            ['name' => 'Ano', 'symbol' => 'ano', 'type' => 'tempo', 'is_active' => true],
            ['name' => 'Pacote', 'symbol' => 'pct', 'type' => 'unidade', 'is_active' => true],
            ['name' => 'Caixa', 'symbol' => 'cx', 'type' => 'unidade', 'is_active' => true],
        ];

        foreach ($units as $unit) {
            DB::table('units')->updateOrInsert(
                ['name' => $unit['name'], 'symbol' => $unit['symbol']],
                $unit
            );
        }

        // Status de Orçamentos
        $budgetStatuses = [
            [
                'slug' => 'draft-novo',
                'name' => 'Rascunho',
                'description' => 'Orçamento em elaboração',
                'color' => '#6c757d',
                'icon' => 'bi-pencil',
                'order_index' => 1,
                'is_active' => true
            ],
            [
                'slug' => 'pending-novo',
                'name' => 'Pendente',
                'description' => 'Aguardando aprovação do cliente',
                'color' => '#ffc107',
                'icon' => 'bi-clock',
                'order_index' => 2,
                'is_active' => true
            ],
            [
                'slug' => 'approved-novo',
                'name' => 'Aprovado',
                'description' => 'Orçamento aprovado pelo cliente',
                'color' => '#28a745',
                'icon' => 'bi-check-circle',
                'order_index' => 3,
                'is_active' => true
            ],
        ];

        foreach ($budgetStatuses as $status) {
            DB::table('budget_statuses')->updateOrInsert(
                ['slug' => $status['slug']],
                $status
            );
        }

        // Primeiro, criar um tenant padrão se não existir
        $defaultTenant = DB::table('tenants')->first();
        if (!$defaultTenant) {
            $tenantId = DB::table('tenants')->insertGetId([
                'name' => 'Tenant Padrão',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $tenantId = $defaultTenant->id;
        }

        // Categorias básicas (requer tenant_id)
        $categories = [
            ['slug' => 'construcao-civil-novo', 'name' => 'Construção Civil', 'tenant_id' => $tenantId],
            ['slug' => 'eletrica-novo', 'name' => 'Elétrica', 'tenant_id' => $tenantId],
            ['slug' => 'hidraulica-novo', 'name' => 'Hidráulica', 'tenant_id' => $tenantId],
            ['slug' => 'tecnologia-novo', 'name' => 'Tecnologia', 'tenant_id' => $tenantId],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug'], 'tenant_id' => $category['tenant_id']],
                $category
            );
        }
    }
}
