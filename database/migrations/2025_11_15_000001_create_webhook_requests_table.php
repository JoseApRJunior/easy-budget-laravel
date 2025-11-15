<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->index();
            $table->string('type')->index();
            $table->string('payload_hash');
            $table->string('status')->default('received');
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['request_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_requests');
    }
};