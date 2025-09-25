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
        Schema::create( 'supports', function ( Blueprint $table ) {
            $table->id();

            // Multi-tenancy
            $table->unsignedBigInteger( 'tenant_id' )->nullable();
            $table->foreign( 'tenant_id' )
                ->references( 'id' )
                ->on( 'tenants' )
                ->onDelete( 'set null' );

            // Dados do suporte
            $table->string( 'first_name', 100 );
            $table->string( 'last_name', 100 );
            $table->string( 'email', 255 );
            $table->string( 'subject', 255 );
            $table->text( 'message' );

            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'supports' );
    }

};