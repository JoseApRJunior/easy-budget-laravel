<?php

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
        Schema::create( 'system_settings', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->onDelete( 'cascade' );

            // Informações da empresa
            $table->string( 'company_name' );
            $table->string( 'contact_email' );
            $table->string( 'phone', 20 )->nullable();
            $table->string( 'website', 255 )->nullable();
            $table->string( 'logo' )->nullable();

            // Configurações regionais
            $table->enum( 'currency', [ 'BRL', 'USD', 'EUR' ] )->default( 'BRL' );
            $table->string( 'timezone' )->default( 'America/Sao_Paulo' );
            $table->string( 'language', 10 )->default( 'pt-BR' );

            // Endereço da empresa
            $table->string( 'address_street' )->nullable();
            $table->string( 'address_number', 20 )->nullable();
            $table->string( 'address_complement', 100 )->nullable();
            $table->string( 'address_neighborhood', 100 )->nullable();
            $table->string( 'address_city', 100 )->nullable();
            $table->string( 'address_state', 50 )->nullable();
            $table->string( 'address_zip_code', 10 )->nullable();
            $table->string( 'address_country', 50 )->nullable();

            // Configurações de sistema
            $table->boolean( 'maintenance_mode' )->default( false );
            $table->text( 'maintenance_message' )->nullable();
            $table->boolean( 'registration_enabled' )->default( true );
            $table->boolean( 'email_verification_required' )->default( true );

            // Configurações de segurança
            $table->integer( 'session_lifetime' )->default( 120 ); // minutos
            $table->integer( 'max_login_attempts' )->default( 5 );
            $table->integer( 'lockout_duration' )->default( 15 ); // minutos

            // Configurações de arquivos
            $table->json( 'allowed_file_types' )->nullable();
            $table->integer( 'max_file_size' )->default( 2048 ); // KB

            // Preferências customizadas do sistema (JSON)
            $table->json( 'system_preferences' )->nullable();

            $table->timestamps();

            // Índices
            $table->unique( 'tenant_id' );
            $table->index( [ 'tenant_id', 'maintenance_mode' ] );
            $table->index( [ 'tenant_id', 'created_at' ] );
            $table->index( 'contact_email' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'system_settings' );
    }

};
