<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Impressão - Easy Budget')</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-size: 14px;
            background-color: #fff;
            color: #333;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .print-header {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                font-size: 12px;
                background-color: #fff !important;
                margin: 0;
                padding: 0;
            }

            .container {
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .card {
                border: 1px solid #dee2e6 !important;
                break-inside: avoid;
            }

            .badge {
                border: 1px solid #dee2e6;
                color: #000 !important;
                background-color: transparent !important;
            }

            .info-box {
                break-inside: avoid;
            }
        }

        .info-box {
            background-color: #f1f3f5;
            border: 1px solid #ced4da;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .total-highlight {
            background-color: #d4edda;
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
        }

        @yield('styles')
    </style>
</head>

<body>
    <div class="container py-4">
        <!-- Toolbar (Hidden on Print) -->
        <div class="d-flex justify-content-center gap-2 mb-4 no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer me-2"></i>Imprimir
            </button>
            @yield('actions')
            <button onclick="window.close()" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg me-2"></i>Fechar
            </button>
        </div>

        <!-- Main Content -->
        <main>
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="text-center mt-5 pt-4 border-top">
            <p class="text-muted small">
                Documento gerado em {{ date('d/m/Y \à\s H:i:s') }}
            </p>
            @yield('footer')
        </footer>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
