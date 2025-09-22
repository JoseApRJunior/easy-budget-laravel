<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Profession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction( function () {
            $professionsData = [ 
                [ 1, 'others', 'Outros' ],
                [ 2, 'lawyer', 'Advogado' ],
                [ 3, 'architect', 'Arquiteto' ],
                [ 4, 'artist', 'Artista' ],
                [ 5, 'biologist', 'Biólogo' ],
                [ 6, 'chef', 'Chef de Cozinha' ],
                [ 7, 'scientist', 'Cientista' ],
                [ 8, 'political_scientist', 'Cientista Político' ],
                [ 9, 'accountant', 'Contador' ],
                [ 10, 'consultant', 'Consultor' ],
                [ 11, 'dentist', 'Dentista' ],
                [ 12, 'designer', 'Designer' ],
                [ 13, 'economist', 'Economista' ],
                [ 14, 'nurse', 'Enfermeiro' ],
                [ 15, 'engineer', 'Engenheiro' ],
                [ 16, 'writer', 'Escritor' ],
                [ 17, 'it_specialist', 'Especialista em TI' ],
                [ 18, 'pharmacist', 'Farmacêutico' ],
                [ 19, 'physicist', 'Físico' ],
                [ 20, 'historian', 'Historiador' ],
                [ 21, 'journalist', 'Jornalista' ],
                [ 22, 'linguist', 'Linguista' ],
                [ 23, 'mathematician', 'Matemático' ],
                [ 24, 'doctor', 'Médico' ],
                [ 25, 'musician', 'Músico' ],
                [ 26, 'pilot', 'Piloto' ],
                [ 27, 'teacher', 'Professor' ],
                [ 28, 'psychologist', 'Psicólogo' ],
                [ 29, 'psychiatrist', 'Psiquiatra' ],
                [ 30, 'geologist', 'Geólogo' ],
                [ 31, 'sociologist', 'Sociólogo' ],
                [ 32, 'technician', 'Técnico' ],
                [ 33, 'veterinarian', 'Veterinário' ],
            ];

            $now  = now();
            $rows = [];
            foreach ( $professionsData as $data ) {
                $rows[] = [ 
                    'id'         => $data[ 0 ],
                    'slug'       => $data[ 1 ],
                    'name'       => $data[ 2 ],
                    'is_active'  => 1,
                    'updated_at' => $now,
                ];
            }
            DB::table( 'professions' )->upsert(
                $rows,
                [ 'slug' ],
                [ 'name', 'is_active', 'updated_at' ],
            );
        } );
    }

}
