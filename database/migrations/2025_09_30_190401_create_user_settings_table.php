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
        Schema::create( 'user_settings', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'user_id' )->constrained( 'users' )->onDelete( 'cascade' );
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->onDelete( 'cascade' );

            // Campos de perfil
            $table->string( 'avatar' )->nullable();
            $table->string( 'full_name' )->nullable();
            $table->text( 'bio' )->nullable();
            $table->string( 'phone', 20 )->nullable();
            $table->date( 'birth_date' )->nullable();

            // Redes sociais
            $table->string( 'social_facebook' )->nullable();
            $table->string( 'social_twitter' )->nullable();
            $table->string( 'social_linkedin' )->nullable();
            $table->string( 'social_instagram' )->nullable();

            // Preferências de interface
            $table->enum( 'theme', [ 'light', 'dark', 'auto' ] )->default( 'auto' );
            $table->string( 'primary_color', 7 )->default( '#3B82F6' );
            $table->enum( 'layout_density', [ 'compact', 'normal', 'spacious' ] )->default( 'normal' );
            $table->enum( 'sidebar_position', [ 'left', 'right' ] )->default( 'left' );
            $table->boolean( 'animations_enabled' )->default( true );
            $table->boolean( 'sound_enabled' )->default( true );

            // Preferências de notificação
            $table->boolean( 'email_notifications' )->default( true );
            $table->boolean( 'transaction_notifications' )->default( true );
            $table->boolean( 'weekly_reports' )->default( false );
            $table->boolean( 'security_alerts' )->default( true );
            $table->boolean( 'newsletter_subscription' )->default( false );
            $table->boolean( 'push_notifications' )->default( false );

            // Preferências customizadas (JSON)
            $table->json( 'custom_preferences' )->nullable();

            $table->timestamps();

            // Índices
            $table->unique( [ 'user_id', 'tenant_id' ] );
            $table->index( [ 'tenant_id', 'theme' ] );
            $table->index( [ 'tenant_id', 'created_at' ] );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'user_settings' );
    }

};
