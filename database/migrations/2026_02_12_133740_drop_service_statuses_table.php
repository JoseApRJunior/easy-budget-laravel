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
        Schema::dropIfExists('service_statuses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('service_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 20)->unique();
            $table->string('name', 50)->unique();
            $table->string('description', 500)->nullable();
            $table->string('color', 7)->nullable();
            $table->string('icon', 30)->nullable();
            $table->integer('order_index')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
