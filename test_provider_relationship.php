<?php

require_once 'vendor/autoload.php';

use App\Models\Tenant;
use App\Models\User;
use App\Models\Provider;

// Simular teste do relacionamento
try {
    // Buscar primeiro tenant
    $tenant = Tenant::first();

    if ( !$tenant ) {
        echo "Nenhum tenant encontrado para teste.\n";
        exit( 1 );
    }

    echo "Tenant ID: {$tenant->id}\n";

    // Testar relacionamento provider()
    $provider = $tenant->provider;

    if ( $provider ) {
        echo "✅ Provider encontrado via relacionamento!\n";
        echo "Provider ID: {$provider->id}\n";
        echo "Provider User ID: {$provider->user_id}\n";

        // Verificar se o provider pertence ao usuário correto
        $user = $provider->user;
        if ( $user && $user->tenant_id === $tenant->id ) {
            echo "✅ Relacionamento consistente: Provider → User → Tenant\n";
        } else {
            echo "❌ Inconsistência no relacionamento\n";
        }
    } else {
        echo "❌ Provider não encontrado via relacionamento\n";

        // Verificar se existe provider para este tenant via User
        $user = $tenant->user;
        if ( $user ) {
            $providerViaUser = $user->provider;
            if ( $providerViaUser ) {
                echo "⚠️  Provider existe via User, mas não via Tenant\n";
                echo "Isso indica problema no hasOneThrough\n";
            } else {
                echo "ℹ️  Nenhum provider encontrado para este tenant\n";
            }
        }
    }

} catch ( Exception $e ) {
    echo "Erro durante teste: " . $e->getMessage() . "\n";
}
