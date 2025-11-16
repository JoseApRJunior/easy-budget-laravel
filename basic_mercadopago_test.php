<?php
// Script simples para testar integração Mercado Pago
echo "=== TESTE MERCADO PAGO ===\n";
echo "Iniciando testes...\n";

// Testar se o arquivo .env existe
if ( file_exists( '.env' ) ) {
    echo "✓ Arquivo .env encontrado\n";

    // Ler configurações do .env
    $envContent = file_get_contents( '.env' );

    if ( strpos( $envContent, 'MERCADOPAGO_ACCESS_TOKEN' ) !== false ) {
        echo "✓ Configuração Mercado Pago encontrada no .env\n";
    } else {
        echo "✗ Configuração Mercado Pago não encontrada\n";
    }

    if ( strpos( $envContent, 'MAIL_HOST' ) !== false ) {
        echo "✓ Configuração de email encontrada\n";
    } else {
        echo "✗ Configuração de email não encontrada\n";
    }

} else {
    echo "✗ Arquivo .env não encontrado\n";
}

// Testar conexão com banco de dados
try {
    $pdo = new PDO( 'mysql:host=localhost;dbname=easybudget', 'root', '' );
    echo "✓ Conexão com banco de dados estabelecida\n";

    // Buscar usuário de teste
    $stmt = $pdo->prepare( "SELECT * FROM users WHERE email = ?" );
    $stmt->execute( [ 'juniorklan.ju@gmail.com' ] );
    $user = $stmt->fetch( PDO::FETCH_ASSOC );

    if ( $user ) {
        echo "✓ Usuário de teste encontrado: " . $user[ 'name' ] . "\n";
        echo "✓ Tenant ID: " . $user[ 'tenant_id' ] . "\n";

        // Buscar credenciais Mercado Pago
        $stmt = $pdo->prepare( "SELECT * FROM provider_mercado_pago_credentials WHERE tenant_id = ?" );
        $stmt->execute( [ $user[ 'tenant_id' ] ] );
        $credentials = $stmt->fetch( PDO::FETCH_ASSOC );

        if ( $credentials ) {
            echo "✓ Credenciais Mercado Pago encontradas\n";
            echo "✓ Access Token: " . substr( $credentials[ 'access_token' ], 0, 10 ) . "...\n";
        } else {
            echo "✗ Nenhuma credencial Mercado Pago encontrada\n";
        }

        // Buscar planos
        $stmt = $pdo->prepare( "SELECT * FROM plans WHERE active = 1" );
        $stmt->execute();
        $plans = $stmt->fetchAll( PDO::FETCH_ASSOC );

        if ( count( $plans ) > 0 ) {
            echo "✓ " . count( $plans ) . " planos ativos encontrados\n";
            foreach ( $plans as $plan ) {
                echo "   - " . $plan[ 'name' ] . ": R$ " . number_format( $plan[ 'price' ], 2, ',', '.' ) . "\n";
            }
        } else {
            echo "✗ Nenhum plano ativo encontrado\n";
        }

        // Buscar faturas
        $stmt = $pdo->prepare( "SELECT * FROM invoices WHERE tenant_id = ?" );
        $stmt->execute( [ $user[ 'tenant_id' ] ] );
        $invoices = $stmt->fetchAll( PDO::FETCH_ASSOC );

        if ( count( $invoices ) > 0 ) {
            echo "✓ " . count( $invoices ) . " faturas encontradas\n";
            foreach ( array_slice( $invoices, 0, 3 ) as $invoice ) {
                echo "   - Fatura #" . $invoice[ 'id' ] . ": R$ " . number_format( $invoice[ 'total' ], 2, ',', '.' ) . " (" . $invoice[ 'status' ] . ")\n";
            }
        } else {
            echo "✗ Nenhuma fatura encontrada\n";
        }

    } else {
        echo "✗ Usuário de teste não encontrado\n";
    }

} catch ( Exception $e ) {
    echo "✗ Erro na conexão com banco de dados: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE FINALIZADO ===\n";
echo "Email de teste: juniorklan.ju@gmail.com\n";
