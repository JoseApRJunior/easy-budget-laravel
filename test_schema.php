<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( 'Illuminate\Contracts\Console\Kernel' )->bootstrap();

echo "Testing database schema...\n";

try {
    $hasTable = \Illuminate\Support\Facades\Schema::hasTable( 'budgets' );
    echo $hasTable ? "Budgets table exists\n" : "Budgets table does not exist\n";

    if ( $hasTable ) {
        $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn( 'budgets', 'budget_statuses_id' );
        echo $hasColumn ? "budget_statuses_id column exists\n" : "budget_statuses_id column does not exist\n";

        if ( $hasColumn ) {
            $columnType = \Illuminate\Support\Facades\Schema::getColumnType( 'budgets', 'budget_statuses_id' );
            echo "Column type: " . $columnType . "\n";
        }
    }
} catch ( Exception $e ) {
    echo "Error: " . $e->getMessage() . "\n";
}
