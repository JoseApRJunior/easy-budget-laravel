{{-- Exemplo de uso das diretivas Blade convertidas das macros Twig --}}

<!DOCTYPE html>
<html>

<head>
    <title>Exemplo de Uso das Diretivas Blade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">

        {{-- Exemplo de uso da diretiva @alert (convertida da macro alerts.twig) --}}
        <h2>Exemplo de Alertas</h2>

        {{-- Alerta de erro --}}
        @alert( 'error', 'Ocorreu um erro ao processar sua solicitação.' )

        {{-- Alerta de sucesso --}}
        @alert( 'success', 'Operação realizada com sucesso!' )

        {{-- Alerta de informação --}}
        @alert( 'message', 'Esta é uma informação importante.' )

        {{-- Alerta de aviso --}}
        @alert( 'warning', 'Atenção: Verifique os dados antes de continuar.' )

        {{-- Exemplo de uso da diretiva @checkFeature (convertida da macro utils.twig) --}}
        <h2 class="mt-5">Exemplo de Verificação de Recursos</h2>

        {{-- Verificação de recurso ativo --}}
        @checkFeature( 'reports', '<div class="card"><div class="card-body"><h5>Relatórios Avançados</h5><p>Visualize relatórios detalhados do sistema.</p></div></div>' )

        {{-- Verificação de recurso inativo (com condição adicional) --}}
        @checkFeature( 'advanced-analytics', '<div class="card"><div class="card-body"><h5>Analytics Avançado</h5><p>Recurso temporariamente desativado.</p></div></div>', false )

        {{-- Verificação com conteúdo customizado --}}
        @checkFeature( 'user-management', '<button class="btn btn-primary">Gerenciar Usuários</button>' )

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
