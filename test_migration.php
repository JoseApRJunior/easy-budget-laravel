<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( 'Illuminate\Contracts\Console\Kernel' )->bootstrap();

echo "Testing migration execution...\n";

try {
    // Check if we can create a budget with enum value
    $budget                     = new \App\Models\Budget();
    $budget->code               = 'TEST-' . time();
    $budget->total              = 1000;
    $budget->discount           = 0;
    $budget->budget_statuses_id = 'draft';
    $budget->tenant_id          = 1;
    $budget->customer_id        = 1;

    $budget->save();
    echo "Budget created successfully with enum value\n";

    // Check the actual value in database
    $savedBudget = \App\Models\Budget::find( $budget->id );
    echo "Saved budget_statuses_id: " . $savedBudget->budget_statuses_id->value . " (type: " . gettype( $savedBudget->budget_statuses_id ) . ")\n";

} catch ( Exception $e ) {
    echo "Error: " . $e->getMessage() . "\n";
}
