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
            $table->string('code', 20)->nullable()->after('id')->unique();
        });

        // Preencher registros existentes
        \Illuminate\Support\Facades\DB::table('schedules')->orderBy('id')->chunk(100, function ($schedules) {
            foreach ($schedules as $schedule) {
                $date = \Carbon\Carbon::parse($schedule->created_at ?? now());
                $code = 'AGD-'.$date->format('Y').'-'.$date->format('m').'-'.str_pad((string) $schedule->id, 6, '0', STR_PAD_LEFT);

                \Illuminate\Support\Facades\DB::table('schedules')
                    ->where('id', $schedule->id)
                    ->update(['code' => $code]);
            }
        });

        // Tornar obrigatório após preencher
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('code', 20)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
