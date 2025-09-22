<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreaOfActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction( function () {
            $areasData = [ 
                [ 1, 'others', 'Outros' ],
                [ 2, 'aerospace', 'Aeroespacial' ],
                [ 3, 'agriculture', 'Agricultura' ],
                [ 4, 'food_and_beverage', 'Alimentos e Bebidas' ],
                [ 5, 'animation', 'Animação' ],
                [ 6, 'analytics', 'Análise de Dados' ],
                [ 7, 'mobile_app', 'Aplicativo Móvel' ],
                [ 8, 'architecture', 'Arquitetura' ],
                [ 9, 'art', 'Arte' ],
                [ 10, 'plan-subscription', 'Assinatura de Plano' ],
                [ 11, 'automotive', 'Automotivo' ],
                [ 12, 'biotechnology', 'Biotecnologia' ],
                [ 13, 'blockchain', 'Blockchain' ],
                [ 14, 'venture_capital', 'Capital de Risco' ],
                [ 15, 'supply_chain', 'Cadeia de Suprimentos' ],
                [ 16, 'film', 'Cinema' ],
                [ 17, 'data_science', 'Ciência de Dados' ],
                [ 18, 'retail', 'Comércio' ],
                [ 19, 'e_commerce', 'Comércio Eletrônico' ],
                [ 20, 'cloud_computing', 'Computação em Nuvem' ],
                [ 21, 'construction', 'Construção' ],
                [ 22, 'consulting', 'Consultoria' ],
                [ 23, 'accounting', 'Contabilidade' ],
                [ 24, 'parts-control', 'Controle de Peças' ],
                [ 25, 'web_development', 'Desenvolvimento Web' ],
                [ 26, 'design', 'Design' ],
                [ 27, 'interior_design', 'Design de Interiores' ],
                [ 28, 'education', 'Educação' ],
                [ 29, 'energy', 'Energia' ],
                [ 30, 'e_learning', 'Ensino a Distância' ],
                [ 31, 'entertainment', 'Entretenimento' ],
                [ 32, 'sports', 'Esportes' ],
                [ 33, 'pharmaceuticals', 'Farmacêutica' ],
                [ 34, 'billing', 'Faturamento' ],
                [ 35, 'fintech', 'Fintech' ],
                [ 36, 'finance', 'Financeiro' ],
                [ 37, 'photography', 'Fotografia' ],
                [ 38, 'franchise', 'Franquia' ],
                [ 39, 'team-management', 'Gestão de Equipe' ],
                [ 40, 'waste_management', 'Gestão de Resíduos' ],
                [ 41, 'government', 'Governo' ],
                [ 42, 'hardware', 'Hardware' ],
                [ 43, 'hospitality', 'Hospitalidade' ],
                [ 44, 'real_estate', 'Imobiliário' ],
                [ 45, 'industrial', 'Indústria' ],
                [ 46, 'whatsapp-integration', 'Integração WhatsApp' ],
                [ 47, 'artificial_intelligence', 'Inteligência Artificial' ],
                [ 48, 'gaming', 'Jogos' ],
                [ 49, 'journalism', 'Jornalismo' ],
                [ 50, 'logistics', 'Logística' ],
                [ 51, 'manufacturing', 'Manufatura' ],
                [ 52, 'marketing', 'Marketing' ],
                [ 53, 'digital_marketing', 'Marketing Digital' ],
                [ 54, 'environment', 'Meio Ambiente' ],
                [ 55, 'media', 'Mídia' ],
                [ 56, 'mining', 'Mineração' ],
                [ 57, 'music', 'Música' ],
                [ 58, 'non_profit', 'Organizações Sem Fins Lucrativos' ],
                [ 59, 'budgets', 'Orçamentos' ],
                [ 60, 'research', 'Pesquisa' ],
                [ 61, 'biotechnology_research', 'Pesquisa em Biotecnologia' ],
                [ 62, 'private_equity', 'Private Equity' ],
                [ 63, 'publishing', 'Publicação' ],
                [ 64, 'advertising', 'Publicidade' ],
                [ 65, 'chemicals', 'Química' ],
                [ 66, 'recycling', 'Reciclagem' ],
                [ 67, 'public_relations', 'Relações Públicas' ],
                [ 68, 'health', 'Saúde' ],
                [ 69, 'security', 'Segurança' ],
                [ 70, 'biotechnology_services', 'Serviços de Biotecnologia' ],
                [ 71, 'consulting_services', 'Serviços de Consultoria' ],
                [ 72, 'mining_services', 'Serviços de Mineração' ],
                [ 73, 'healthcare_services', 'Serviços de Saúde' ],
                [ 74, 'telecommunications_services', 'Serviços de Telecomunicações' ],
                [ 75, 'tourism_services', 'Serviços de Turismo' ],
                [ 76, 'travel_services', 'Serviços de Viagens' ],
                [ 77, 'education_services', 'Serviços Educacionais' ],
                [ 78, 'software', 'Software' ],
                [ 79, 'telecommunications', 'Telecomunicações' ],
                [ 80, 'outsourcing', 'Terceirização' ],
                [ 81, 'technology', 'Tecnologia' ],
                [ 82, 'vocational_training', 'Treinamento Profissional' ],
                [ 83, 'travel', 'Turismo' ],
            ];

            $now  = now();
            $rows = [];
            foreach ( $areasData as $data ) {
                $rows[] = [ 
                    'id'          => $data[ 0 ],
                    'slug'        => $data[ 1 ],
                    'name'        => $data[ 2 ],
                    'is_active'   => 1,
                    'description' => null,
                    'updated_at'  => $now,
                ];
            }
            DB::table( 'area_of_activities' )->upsert(
                $rows,
                [ 'slug' ],
                [ 'name', 'is_active', 'description', 'updated_at' ],
            );
        } );
    }

}
