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
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('location');
            $table->timestamp('confirmed_at')->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('confirmed_at');
            $table->timestamp('no_show_at')->nullable()->after('completed_at');
            $table->timestamp('cancelled_at')->nullable()->after('no_show_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'confirmed_at',
                'completed_at',
                'no_show_at',
                'cancelled_at',
                'cancellation_reason'
            ]);
        });
    }
};
