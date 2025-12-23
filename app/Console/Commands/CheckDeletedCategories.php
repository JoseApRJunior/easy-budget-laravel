<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDeletedCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:check-deleted';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica categorias deletadas no sistema';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info( 'Verificando categorias deletadas...' );

        // Total de categorias
        $total = DB::table( 'categories' )->count();
        $this->line( "Total de categorias: {$total}" );

        // Categorias deletadas
        $deleted = DB::table( 'categories' )->whereNotNull( 'deleted_at' )->count();
        $this->line( "Categorias deletadas: {$deleted}" );

        // Categorias deletadas com tenant
        $deletedWithTenant = DB::select( 'SELECT COUNT(*) as count FROM categories c JOIN category_tenant ct ON c.id = ct.category_id WHERE c.deleted_at IS NOT NULL AND ct.is_custom = 1' )[ 0 ]->count;
        $this->line( "Categorias deletadas com tenant: {$deletedWithTenant}" );

        // Detalhes das categorias deletadas com tenant
        if ( $deletedWithTenant > 0 ) {
            $this->info( 'Detalhes das categorias deletadas com tenant:' );
            $categories = DB::select( 'SELECT c.id, c.name, c.slug, c.deleted_at, ct.tenant_id FROM categories c JOIN category_tenant ct ON c.id = ct.category_id WHERE c.deleted_at IS NOT NULL AND ct.is_custom = 1 LIMIT 10' );

            foreach ( $categories as $category ) {
                $this->line( "- ID: {$category->id}, Nome: {$category->name}, Tenant: {$category->tenant_id}, Deletada em: {$category->deleted_at}" );
            }
        }

        return 0;
    }

}
