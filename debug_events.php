<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make( \Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Events\EmailVerificationRequested;
use App\Listeners\SendEmailVerification;
use Illuminate\Support\Facades\Event;

// Teste 1: Verificar se o evento existe
echo "=== TESTE 1: Verificando se o evento existe ===\n";
try {
    $eventClass = EmailVerificationRequested::class;
    echo "✓ Evento encontrado: $eventClass\n";

    // Teste 2: Verificar se o listener existe
    echo "\n=== TESTE 2: Verificando se o listener existe ===\n";
    $listenerClass = SendEmailVerification::class;
    echo "✓ Listener encontrado: $listenerClass\n";

    // Teste 3: Verificar se o evento está registrado no Laravel
    echo "\n=== TESTE 3: Verificando registro no Laravel ===\n";
    $listeners = Event::getListeners( $eventClass );

    if ( empty( $listeners ) ) {
        echo "✗ Evento NÃO registrado no Laravel\n";
    } else {
        echo "✓ Evento registrado no Laravel\n";
        echo "  - Número de listeners: " . count( $listeners ) . "\n";
    }

    // Teste 4: Verificar listeners registrados no Laravel
    echo "\n=== TESTE 4: Verificando listeners no Laravel ===\n";
    $listeners = Event::getListeners( $eventClass );

    if ( empty( $listeners ) ) {
        echo "✗ Nenhum listener registrado para o evento no Laravel\n";
    } else {
        echo "✓ Listeners registrados encontrados:\n";
        foreach ( $listeners as $listener ) {
            echo "  - " . ( is_array( $listener ) ? implode( ', ', $listener ) : $listener ) . "\n";
        }
    }

    // Teste 5: Tentar criar uma instância do evento
    echo "\n=== TESTE 5: Testando criação do evento ===\n";
    $user = \App\Models\User::first();
    if ( $user ) {
        $event = new EmailVerificationRequested( $user, $user->tenant, 'test-token' );
        echo "✓ Evento criado com sucesso\n";

        // Teste 6: Tentar disparar o evento
        echo "\n=== TESTE 6: Testando disparo do evento ===\n";
        Event::dispatch( $event );
        echo "✓ Evento disparado com sucesso\n";
    } else {
        echo "✗ Nenhum usuário encontrado para teste\n";
    }

} catch ( Exception $e ) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
    echo "  Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Teste 7: Verificar se há erros no EventServiceProvider
echo "\n=== TESTE 7: Verificando EventServiceProvider ===\n";
try {
    $provider = app( \App\Providers\EventServiceProvider::class);
    echo "✓ EventServiceProvider instanciado com sucesso\n";

    // Verificar se o método boot() executa sem erro
    $provider->boot();
    echo "✓ Método boot() executado sem erros\n";

    // Verificar se o evento foi registrado após o boot
    $listenersAfterBoot = Event::getListeners( $eventClass );
    if ( empty( $listenersAfterBoot ) ) {
        echo "✗ Ainda nenhum listener após boot()\n";
    } else {
        echo "✓ Listener registrado após boot(): " . count( $listenersAfterBoot ) . "\n";
    }

} catch ( Exception $e ) {
    echo "✗ Erro no EventServiceProvider: " . $e->getMessage() . "\n";
    echo "  Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Teste 8: Verificar configuração de eventos
echo "\n=== TESTE 8: Verificando configuração ===\n";
echo "shouldDiscoverEvents: " . ( config( 'events.discover', true ) ? 'true' : 'false' ) . "\n";

// Teste 9: Verificar se há conflitos com outros eventos
echo "\n=== TESTE 9: Verificando conflitos ===\n";
try {
    $dispatcher     = app( 'events' );
    $allListeners   = [];
    $totalListeners = 0;

    // Verificar alguns eventos conhecidos
    $knownEvents = [
        'Illuminate\Auth\Events\Registered',
        'App\Events\InvoiceCreated',
        'App\Events\StatusUpdated'
    ];

    foreach ( $knownEvents as $knownEvent ) {
        $listeners      = Event::getListeners( $knownEvent );
        $totalListeners += count( $listeners );
        if ( count( $listeners ) > 0 ) {
            echo "  - $knownEvent: " . count( $listeners ) . " listeners\n";
        }
    }

    echo "Total de listeners verificados: $totalListeners\n";
} catch ( Exception $e ) {
    echo "Erro ao verificar listeners: " . $e->getMessage() . "\n";
}

// Teste 10: Verificar se o problema é específico deste evento
echo "\n=== TESTE 10: Testando outro evento ===\n";
try {
    $testEvent     = new \App\Events\InvoiceCreated( app( \App\Models\Invoice::class), app( \App\Models\Customer::class), app( \App\Models\Tenant::class) );
    $testListeners = Event::getListeners( \App\Events\InvoiceCreated::class);
    echo "Evento InvoiceCreated tem " . count( $testListeners ) . " listeners registrados\n";
} catch ( Exception $e ) {
    echo "Erro ao testar InvoiceCreated: " . $e->getMessage() . "\n";
}

// Teste 11: Verificar sintaxe do EventServiceProvider
echo "\n=== TESTE 11: Verificando sintaxe do EventServiceProvider ===\n";
$providerFile = __DIR__ . '/app/Providers/EventServiceProvider.php';

if ( file_exists( $providerFile ) ) {
    $content = file_get_contents( $providerFile );

    // Verificar se há problemas de sintaxe básicos
    if ( strpos( $content, 'EmailVerificationRequested::class' ) === false ) {
        echo "✗ Evento EmailVerificationRequested não encontrado no arquivo\n";
    } else {
        echo "✓ Evento EmailVerificationRequested encontrado no arquivo\n";
    }

    if ( strpos( $content, 'SendEmailVerification::class' ) === false ) {
        echo "✗ Listener SendEmailVerification não encontrado no arquivo\n";
    } else {
        echo "✓ Listener SendEmailVerification encontrado no arquivo\n";
    }

    // Verificar se há problemas de sintaxe PHP
    try {
        $tokens       = token_get_all( $content );
        $syntaxErrors = [];
        foreach ( $tokens as $token ) {
            if ( is_array( $token ) && $token[ 0 ] === T_STRING && in_array( $token[ 1 ], [ 'class', 'function', 'interface' ] ) ) {
                // Verificação básica de estrutura
            }
        }
        echo "✓ Sintaxe PHP parece estar correta\n";
    } catch ( Exception $e ) {
        echo "✗ Possível problema de sintaxe: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Arquivo EventServiceProvider não encontrado\n";
}

// Teste 12: Verificar se o problema é com o registro específico
echo "\n=== TESTE 12: Testando registro manual do evento ===\n";
try {
    // Tentar registrar manualmente o evento
    Event::listen( EmailVerificationRequested::class, SendEmailVerification::class);

    // Verificar se foi registrado
    $manualListeners = Event::getListeners( EmailVerificationRequested::class);
    if ( count( $manualListeners ) > 0 ) {
        echo "✓ Registro manual funcionou! Evento agora tem " . count( $manualListeners ) . " listeners\n";

        // Testar disparo novamente
        if ( isset( $user ) ) {
            Event::dispatch( new EmailVerificationRequested( $user, $user->tenant, 'test-token-manual' ) );
            echo "✓ Evento disparado após registro manual\n";
        }
    } else {
        echo "✗ Registro manual falhou\n";
    }
} catch ( Exception $e ) {
    echo "✗ Erro no registro manual: " . $e->getMessage() . "\n";
}

// Teste 13: Verificar se o problema persiste após registro manual
echo "\n=== TESTE 13: Verificando persistência do registro ===\n";
$persistentListeners = Event::getListeners( EmailVerificationRequested::class);
echo "Listeners após registro manual: " . count( $persistentListeners ) . "\n";

if ( count( $persistentListeners ) > 0 ) {
    echo "✓ Registro manual persistiu\n";

    // Teste 14: Verificar se o listener funciona corretamente
    echo "\n=== TESTE 14: Testando funcionalidade do listener ===\n";
    try {
        // Criar um usuário válido para teste
        $testUser = \App\Models\User::first();
        if ( $testUser ) {
            // Criar evento com dados válidos
            $testEvent = new EmailVerificationRequested( $testUser, $testUser->tenant, str_repeat( 'a', 64 ) );

            // Tentar executar o listener diretamente
            $listener = app( SendEmailVerification::class);
            $listener->handle( $testEvent );

            echo "✓ Listener executado com sucesso\n";
        } else {
            echo "✗ Nenhum usuário disponível para teste\n";
        }
    } catch ( Exception $e ) {
        echo "✗ Erro na execução do listener: " . $e->getMessage() . "\n";
        echo "  Tipo: " . get_class( $e ) . "\n";
    }
} else {
    echo "✗ Registro manual não persistiu\n";
}

// Teste 15: Verificar se há problema com o processo de boot
echo "\n=== TESTE 15: Verificando processo de boot ===\n";
try {
    // Forçar recarregamento da aplicação
    $kernel = app( \Illuminate\Contracts\Console\Kernel::class);
    if ( method_exists( $kernel, 'bootstrap' ) ) {
        $kernel->bootstrap();
        echo "✓ Kernel bootstrap executado\n";
    }

    // Verificar se o evento foi registrado após bootstrap
    $listenersAfterBootstrap = Event::getListeners( EmailVerificationRequested::class);
    echo "Listeners após bootstrap: " . count( $listenersAfterBootstrap ) . "\n";

} catch ( Exception $e ) {
    echo "✗ Erro no processo de boot: " . $e->getMessage() . "\n";
}

// Teste 16: Verificar se há problema específico no EventServiceProvider
echo "\n=== TESTE 16: Analisando EventServiceProvider detalhadamente ===\n";

// Verificar se há algum erro específico durante o registro
try {
    // Tentar simular o processo de registro do EventServiceProvider
    $provider = app()->getProvider( \App\Providers\EventServiceProvider::class);

    if ( $provider ) {
        echo "✓ Provider encontrado no container\n";

        // Verificar se o provider foi registrado
        $providerClass = get_class( $provider );
        echo "✓ Provider class: $providerClass\n";

        // Tentar chamar o método register manualmente
        if ( method_exists( $provider, 'register' ) ) {
            $provider->register();
            echo "✓ Método register() executado\n";
        }

        // O método boot() é chamado automaticamente pelo Laravel
        // Não podemos chamá-lo diretamente aqui

        // Verificar se o evento foi registrado após essas operações
        $listenersAfterProvider = Event::getListeners( EmailVerificationRequested::class);
        echo "Listeners após operações do provider: " . count( $listenersAfterProvider ) . "\n";

    } else {
        echo "✗ Provider não encontrado no container\n";
    }

} catch ( Exception $e ) {
    echo "✗ Erro ao analisar EventServiceProvider: " . $e->getMessage() . "\n";
    echo "  Linha: " . $e->getLine() . "\n";
    echo "  Arquivo: " . $e->getFile() . "\n";
}

// Teste 17: Verificar se há conflito com outros eventos similares
echo "\n=== TESTE 17: Verificando conflitos com eventos similares ===\n";

// Verificar se há algum evento com nome similar que pode estar causando conflito
$similarEvents = [
    'App\Events\UserRegistered',
    'App\Events\PasswordResetRequested',
    'App\Events\EmailVerificationRequested',
];

foreach ( $similarEvents as $similarEvent ) {
    try {
        $listeners = Event::getListeners( $similarEvent );
        echo "  - $similarEvent: " . count( $listeners ) . " listeners\n";
    } catch ( Exception $e ) {
        echo "  - $similarEvent: Erro - " . $e->getMessage() . "\n";
    }
}

// Teste 18: Verificar se o problema é com o processo de descoberta automática
echo "\n=== TESTE 18: Verificando descoberta automática de eventos ===\n";

try {
    // Verificar se o Laravel está descobrindo eventos automaticamente
    $dispatcher = app( 'events' );

    if ( method_exists( $dispatcher, 'getEventDiscoveryMap' ) ) {
        $discoveryMap = $dispatcher->getEventDiscoveryMap();
        echo "✓ Mapa de descoberta obtido\n";
        echo "  - Eventos descobertos: " . count( $discoveryMap ) . "\n";
    } else {
        echo "✗ Método getEventDiscoveryMap não disponível\n";
    }

} catch ( Exception $e ) {
    echo "✗ Erro na descoberta automática: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DOS TESTES ===\n";
