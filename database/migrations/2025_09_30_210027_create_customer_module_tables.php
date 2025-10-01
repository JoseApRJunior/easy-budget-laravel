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
        // Tabela de tags para clientes
        Schema::create( 'customer_tags', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained()->onDelete( 'cascade' );
            $table->string( 'name' );
            $table->string( 'color', 7 )->default( '#6B7280' ); // Hex color code
            $table->text( 'description' )->nullable();
            $table->boolean( 'is_active' )->default( true );
            $table->integer( 'sort_order' )->default( 0 );
            $table->timestamps();

            $table->unique( [ 'tenant_id', 'name' ] );
            $table->index( [ 'tenant_id', 'is_active' ] );
        } );

        // Tabela de endereços dos clientes
        Schema::create( 'customer_addresses', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'customer_id' )->constrained()->onDelete( 'cascade' );
            $table->string( 'type' )->default( 'principal' ); // principal, trabalho, filial, etc
            $table->string( 'cep', 9 );
            $table->string( 'street', 255 );
            $table->string( 'number', 20 );
            $table->string( 'complement' )->nullable();
            $table->string( 'neighborhood', 100 );
            $table->string( 'city', 100 );
            $table->string( 'state', 2 );
            $table->decimal( 'latitude', 10, 8 )->nullable();
            $table->decimal( 'longitude', 11, 8 )->nullable();
            $table->text( 'formatted_address' )->nullable();
            $table->boolean( 'is_primary' )->default( false );
            $table->boolean( 'is_active' )->default( true );
            $table->timestamps();

            $table->index( [ 'customer_id', 'is_primary' ] );
            $table->index( [ 'customer_id', 'is_active' ] );
        } );

        // Tabela de contatos dos clientes
        Schema::create( 'customer_contacts', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'customer_id' )->constrained()->onDelete( 'cascade' );
            $table->string( 'type' )->default( 'email' ); // email, phone, whatsapp, linkedin, site
            $table->string( 'label' )->nullable(); // trabalho, pessoal, financeiro, etc
            $table->string( 'value', 255 );
            $table->boolean( 'is_primary' )->default( false );
            $table->boolean( 'is_verified' )->default( false );
            $table->timestamp( 'verified_at' )->nullable();
            $table->text( 'notes' )->nullable();
            $table->boolean( 'is_active' )->default( true );
            $table->timestamps();

            $table->index( [ 'customer_id', 'type' ] );
            $table->index( [ 'customer_id', 'is_primary' ] );
            $table->index( [ 'customer_id', 'is_active' ] );
        } );

        // Tabela de interações com clientes
        Schema::create( 'customer_interactions', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'customer_id' )->constrained()->onDelete( 'cascade' );
            $table->foreignId( 'user_id' )->constrained()->onDelete( 'cascade' );
            $table->string( 'type' ); // call, email, meeting, visit, proposal, note
            $table->string( 'title' );
            $table->text( 'description' )->nullable();
            $table->enum( 'direction', [ 'inbound', 'outbound' ] )->default( 'outbound' );
            $table->timestamp( 'interaction_date' );
            $table->integer( 'duration_minutes' )->nullable();
            $table->enum( 'outcome', [ 'completed', 'pending', 'cancelled', 'rescheduled' ] )->nullable();
            $table->text( 'next_action' )->nullable();
            $table->timestamp( 'next_action_date' )->nullable();
            $table->json( 'attachments' )->nullable(); // Array de arquivos anexados
            $table->json( 'metadata' )->nullable(); // Campos adicionais flexíveis
            $table->boolean( 'notify_customer' )->default( false );
            $table->boolean( 'is_active' )->default( true );
            $table->timestamps();

            $table->index( [ 'customer_id', 'interaction_date' ] );
            $table->index( [ 'customer_id', 'type' ] );
            $table->index( [ 'user_id', 'interaction_date' ] );
            $table->index( [ 'next_action_date' ] );
        } );

        // Tabela de relacionamento many-to-many entre customers e tags
        Schema::create( 'customer_tag_assignments', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'customer_id' )->constrained()->onDelete( 'cascade' );
            $table->foreignId( 'customer_tag_id' )->constrained()->onDelete( 'cascade' );
            $table->timestamps();

            $table->unique( [ 'customer_id', 'customer_tag_id' ] );
            $table->index( [ 'customer_tag_id', 'customer_id' ] );
        } );

        // Tabela melhorada de clientes (mantendo compatibilidade)
        Schema::table( 'customers', function ( Blueprint $table ) {
            if ( !Schema::hasColumn( 'customers', 'customer_type' ) ) {
                $table->enum( 'customer_type', [ 'individual', 'company' ] )->default( 'individual' )->after( 'status' );
            }
            if ( !Schema::hasColumn( 'customers', 'company_name' ) ) {
                $table->string( 'company_name', 255 )->nullable()->after( 'customer_type' );
            }
            if ( !Schema::hasColumn( 'customers', 'fantasy_name' ) ) {
                $table->string( 'fantasy_name', 255 )->nullable()->after( 'company_name' );
            }
            if ( !Schema::hasColumn( 'customers', 'state_registration' ) ) {
                $table->string( 'state_registration', 20 )->nullable()->after( 'fantasy_name' );
            }
            if ( !Schema::hasColumn( 'customers', 'municipal_registration' ) ) {
                $table->string( 'municipal_registration', 20 )->nullable()->after( 'state_registration' );
            }
            if ( !Schema::hasColumn( 'customers', 'priority_level' ) ) {
                $table->enum( 'priority_level', [ 'normal', 'vip', 'premium' ] )->default( 'normal' )->after( 'municipal_registration' );
            }
            if ( !Schema::hasColumn( 'customers', 'last_interaction_at' ) ) {
                $table->timestamp( 'last_interaction_at' )->nullable()->after( 'priority_level' );
            }
            if ( !Schema::hasColumn( 'customers', 'total_interactions' ) ) {
                $table->integer( 'total_interactions' )->default( 0 )->after( 'last_interaction_at' );
            }
            if ( !Schema::hasColumn( 'customers', 'metadata' ) ) {
                $table->json( 'metadata' )->nullable()->after( 'total_interactions' );
            }
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'customer_tag_assignments' );
        Schema::dropIfExists( 'customer_interactions' );
        Schema::dropIfExists( 'customer_contacts' );
        Schema::dropIfExists( 'customer_addresses' );
        Schema::dropIfExists( 'customer_tags' );

        // Reverter alterações na tabela customers
        Schema::table( 'customers', function ( Blueprint $table ) {
            $table->dropColumn( [
                'customer_type',
                'company_name',
                'fantasy_name',
                'state_registration',
                'municipal_registration',
                'priority_level',
                'last_interaction_at',
                'total_interactions',
                'metadata',
            ] );
        } );
    }

};
