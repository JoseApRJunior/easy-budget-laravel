<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveCashierTablesRobust extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop subscription_items table if exists
        if (Schema::hasTable('subscription_items')) {
            Schema::dropIfExists('subscription_items');
        }

        // Drop subscriptions table if exists
        if (Schema::hasTable('subscriptions')) {
            Schema::dropIfExists('subscriptions');
        }

        // Drop Cashier columns from users table if they exist
        Schema::table('users', function (Blueprint $table) {
            $columns = ['stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at'];
            $columnsToDrop = [];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                // Drop index if exists (usually on stripe_id)
                // We use a try-catch block because checking for index existence is database-specific
                try {
                    $table->dropIndex(['stripe_id']);
                } catch (\Exception $e) {
                    // Index might not exist or be named differently, ignore
                }
                
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We cannot easily reverse this migration because we are deleting data.
        // But for completeness, we can re-add the columns and tables structure.

        if (!Schema::hasColumn('users', 'stripe_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('stripe_id')->nullable()->index();
                $table->string('pm_type')->nullable();
                $table->string('pm_last_four', 4)->nullable();
                $table->timestamp('trial_ends_at')->nullable();
            });
        }

        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->string('type');
                $table->string('stripe_id')->unique();
                $table->string('stripe_status');
                $table->string('stripe_price')->nullable();
                $table->integer('quantity')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'stripe_status']);
            });
        }

        if (!Schema::hasTable('subscription_items')) {
            Schema::create('subscription_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subscription_id');
                $table->string('stripe_id')->unique();
                $table->string('stripe_product');
                $table->string('stripe_price');
                $table->integer('quantity')->nullable();
                $table->timestamps();

                $table->unique(['subscription_id', 'stripe_price']);
            });
        }
    }
}
