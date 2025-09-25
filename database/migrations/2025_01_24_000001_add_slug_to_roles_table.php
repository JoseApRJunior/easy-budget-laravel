<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Migration para adicionar coluna slug à tabela roles.
 * 
 * Esta migration adiciona a coluna slug que é necessária para o funcionamento
 * do RoleRepository e dos testes relacionados.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adicionar coluna slug se não existir
        if (!Schema::hasColumn('roles', 'slug')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
            });
        }

        // Popular slugs vazios baseados no campo name
        $roles = DB::table('roles')->whereNull('slug')->orWhere('slug', '')->get();
        foreach ($roles as $role) {
            $slug = Str::slug($role->name);
            
            // Garantir unicidade do slug
            $originalSlug = $slug;
            $counter = 1;
            while (DB::table('roles')->where('slug', $slug)->where('id', '!=', $role->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            DB::table('roles')->where('id', $role->id)->update(['slug' => $slug]);
        }

        // Tornar a coluna unique se ainda não for
        Schema::table('roles', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};