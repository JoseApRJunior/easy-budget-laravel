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
        Schema::create( 'contacts', function (Blueprint $table) {
            $table->bigIncrements( 'id' ); // ID único do contato

            // Colunas principais
            $table->unsignedBigInteger( 'tenant_id' )->index();
            // Comentário: ID do tenant associado ao contato, garantindo isolamento multi-tenant conforme Clean Architecture

            $table->unsignedBigInteger( 'provider_id' )->nullable()->index();
            // Comentário: ID do provedor relacionado ao contato, nullable se o contato não estiver vinculado a um provedor específico

            $table->string( 'name', 100 );
            // Comentário: Nome completo do contato, limitado a 100 caracteres para eficiência em consultas

            $table->string( 'email', 100 )->nullable();
            // Comentário: Endereço de email principal do contato, nullable para contatos sem email

            $table->string( 'phone', 20 )->nullable();
            // Comentário: Número de telefone principal, formato string para suportar variações internacionais, nullable

            $table->enum( 'type', [ 'primary', 'secondary', 'billing' ] )->default( 'primary' );
            // Comentário: Tipo de contato, com opções para contato primário, secundário ou de cobrança

            // Colunas extras do modelo legacy
            $table->string( 'email_business', 100 )->nullable();
            // Comentário: Email comercial adicional, herdado do schema legacy para compatibilidade

            $table->string( 'phone_business', 20 )->nullable();
            // Comentário: Telefone comercial adicional, herdado do schema legacy

            $table->string( 'website', 255 )->nullable();
            // Comentário: Site ou URL do contato, nullable, com limite maior para URLs completas

            $table->text( 'notes' )->nullable();
            // Comentário: Notas adicionais sobre o contato, campo text para descrições longas, nullable (schema legacy)

            $table->timestamps(); // Timestamps para created_at e updated_at
        } );

        // Chaves estrangeiras (FKs)
        Schema::table( 'contacts', function (Blueprint $table) {
            $table->foreign( 'tenant_id' )
                ->references( 'id' )
                ->on( 'tenants' )
                ->onDelete( 'cascade' );
            // Comentário: FK para tenants.id, com cascade delete para manter integridade em multi-tenant

            $table->foreign( 'provider_id' )
                ->references( 'id' )
                ->on( 'providers' )
                ->onDelete( 'set null' );
            // Comentário: FK para providers.id, set null em delete para não perder contatos órfãos
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'contacts' );
    }

};
