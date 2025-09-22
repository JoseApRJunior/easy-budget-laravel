<?php

declare(strict_types=1);

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create( 'addresses', function (Blueprint $table) {
            $table->bigIncrements( 'id' ); // ID único do endereço

            // Colunas principais
            $table->unsignedBigInteger( 'tenant_id' )->index();
            // Comentário: ID do tenant associado ao endereço, garantindo isolamento multi-tenant conforme Clean Architecture

            $table->unsignedBigInteger( 'contact_id' )->nullable()->index();
            // Comentário: ID do contato relacionado ao endereço, nullable se o endereço não estiver vinculado a um contato específico

            $table->string( 'street', 200 );
            // Comentário: Rua ou endereço principal, limitado a 200 caracteres para descrições detalhadas

            $table->string( 'number', 20 );
            // Comentário: Número do endereço, string para suportar sufixos como 'A' ou 'Apt 101'

            $table->string( 'city', 100 );
            // Comentário: Cidade do endereço, essencial para localização geográfica

            $table->string( 'state', 100 );
            // Comentário: Estado ou província, para organização regional

            $table->string( 'zip', 20 );
            // Comentário: Código postal ou CEP, string para formatos variados (ex: CEP brasileiro)

            $table->string( 'country', 100 )->default( 'Brazil' );
            // Comentário: País do endereço, default 'Brazil' para foco no mercado brasileiro

            $table->enum( 'type', [ 'billing', 'shipping', 'primary' ] )->default( 'primary' );
            // Comentário: Tipo de endereço, com opções para cobrança, envio ou principal

            // Colunas extras do modelo legacy
            $table->string( 'neighborhood', 100 )->nullable();
            // Comentário: Bairro ou vizinhança, herdado do schema legacy para compatibilidade

            $table->boolean( 'is_default' )->default( false );
            // Comentário: Indica se é o endereço padrão, boolean para flag simples (schema legacy)

            $table->timestamps(); // Timestamps para created_at e updated_at
        } );

        // Chaves estrangeiras (FKs)
        Schema::table( 'addresses', function (Blueprint $table) {
            $table->foreign( 'tenant_id' )
                ->references( 'id' )
                ->on( 'tenants' )
                ->onDelete( 'cascade' );
            // Comentário: FK para tenants.id, com cascade delete para manter integridade em multi-tenant

            $table->foreign( 'contact_id' )
                ->references( 'id' )
                ->on( 'contacts' )
                ->onDelete( 'set null' );
            // Comentário: FK para contacts.id, set null em delete para não perder endereços órfãos
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'addresses' );
    }

};
