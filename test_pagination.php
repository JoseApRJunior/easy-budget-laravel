<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

echo "Testing CategoryRepository paginated method...\n";

// Executar comando artisan para testar
$command = 'cd ' . __DIR__ . ' && php artisan tinker --execute="
use App\Repositories\CategoryRepository;
use Illuminate\Support\Facades\Auth;

echo \"Testing CategoryRepository paginated method...\" . PHP_EOL;

try {
    \$repo = new CategoryRepository();

    // Testar paginação básica
    \$paginator = \$repo->getPaginated([], 5);
    echo \"Total items: \" . \$paginator->total() . PHP_EOL;
    echo \"Per page: \" . \$paginator->perPage() . PHP_EOL;
    echo \"Current page: \" . \$paginator->currentPage() . PHP_EOL;
    echo \"Items on this page: \" . \$paginator->count() . PHP_EOL;
    echo \"Has more pages: \" . (\$paginator->hasMorePages() ? \"Yes\" : \"No\") . PHP_EOL;

    if (\$paginator->total() > 0) {
        echo \"First item: \" . \$paginator->firstItem() . PHP_EOL;
        echo \"Last item: \" . \$paginator->lastItem() . PHP_EOL;
        echo \"First category name: \" . \$paginator->first()->name . PHP_EOL;
    }

    echo \"Test completed successfully!\" . PHP_EOL;
} catch (Exception \$e) {
    echo \"Error: \" . \$e->getMessage() . PHP_EOL;
}
"';

echo "Running command...\n";
system( $command );
