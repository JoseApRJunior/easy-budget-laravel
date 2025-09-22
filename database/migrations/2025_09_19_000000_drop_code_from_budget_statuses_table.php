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
        Schema::table( 'budget_statuses', function (Blueprint $table) {
            $table->dropUnique( 'budget_statuses_code_unique' );
            $table->dropColumn( 'code' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'budget_statuses', function (Blueprint $table) {
            $table->string( 'code' )->unique()->after( 'id' );
        } );
    }

};
