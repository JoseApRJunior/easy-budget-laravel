<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantSeedBaseCommand extends Command
{
    protected $signature = 'tenant:seed-base {tenant_id : ID do tenant} {--source-tenant= : ID de um tenant modelo para copiar como base, se necessário} {--force : Executa sem confirmação}';

    protected $description = 'Anexa categorias base do sistema (categories.tenant_id IS NULL) ao tenant informado.';

    public function handle(): int
    {
        $tenantId       = (int) $this->argument( 'tenant_id' );
        $sourceTenantId = $this->option( 'source-tenant' ) ? (int) $this->option( 'source-tenant' ) : null;
        $force          = (bool) $this->option( 'force' );

        if ( !$force ) {
            if ( !$this->confirm( "Anexar categorias base ao tenant {$tenantId}?", true ) ) {
                $this->warn( 'Operação cancelada.' );

                return self::SUCCESS;
            }
        }

        $baseCategoryIds = DB::table( 'categories' )
            ->whereNull( 'tenant_id' )
            ->pluck( 'id' )
            ->all();

        if ( empty( $baseCategoryIds ) ) {
            if ( $sourceTenantId ) {
                $this->line( "Nenhuma categoria base encontrada. Copiando do tenant {$sourceTenantId} para base..." );
                $sourceCats = DB::table( 'categories' )
                    ->where( 'tenant_id', $sourceTenantId )
                    ->orderBy( 'parent_id' )
                    ->orderBy( 'name' )
                    ->get( [ 'id', 'name', 'slug', 'parent_id', 'is_active' ] );

                if ( $sourceCats->isEmpty() ) {
                    $this->warn( 'Tenant de origem não possui categorias para copiar.' );

                    return self::SUCCESS;
                }

                $map        = [];
                $remaining  = $sourceCats->toArray();
                $iterations = 0;
                while ( !empty( $remaining ) && $iterations < 10000 ) {
                    $iterations++;
                    $next = [];
                    foreach ( $remaining as $sc ) {
                        $sc       = (array) $sc;
                        $parentId = $sc[ 'parent_id' ];
                        if ( $parentId !== null && !isset( $map[ $parentId ] ) ) {
                            $next[] = $sc;

                            continue;
                        }
                        $existsSlug = DB::table( 'categories' )
                            ->whereNull( 'tenant_id' )
                            ->where( 'slug', $sc[ 'slug' ] )
                            ->exists();
                        if ( $existsSlug ) {
                            $baseId         = DB::table( 'categories' )
                                ->whereNull( 'tenant_id' )
                                ->where( 'slug', $sc[ 'slug' ] )
                                ->value( 'id' );
                            $map[ $sc[ 'id' ] ] = $baseId;

                            continue;
                        }
                        $newId          = DB::table( 'categories' )->insertGetId( [
                            'tenant_id' => null,
                            'name'      => $sc[ 'name' ],
                            'slug'      => $sc[ 'slug' ],
                            'parent_id' => $parentId ? ( $map[ $parentId ] ?? null ) : null,
                            'is_active' => (bool) $sc[ 'is_active' ],
                        ] );
                        $map[ $sc[ 'id' ] ] = $newId;
                    }
                    $remaining = $next;
                }
                $baseCategoryIds = DB::table( 'categories' )
                    ->whereNull( 'tenant_id' )
                    ->pluck( 'id' )
                    ->all();
            } else {
                $this->info( 'Nenhuma categoria base encontrada. Informe --source-tenant={id} para copiar de um tenant modelo.' );

                return self::SUCCESS;
            }
        }

        $attached = 0;
        foreach ( $baseCategoryIds as $cid ) {
            $exists = DB::table( 'category_tenant' )
                ->where( 'tenant_id', $tenantId )
                ->where( 'category_id', $cid )
                ->exists();
            if ( !$exists ) {
                DB::table( 'category_tenant' )->insert( [
                    'tenant_id'   => $tenantId,
                    'category_id' => $cid,
                ] );
                $attached++;
            }
        }

        $this->info( "Anexadas {$attached} categorias base ao tenant {$tenantId}." );

        return self::SUCCESS;
    }

}
