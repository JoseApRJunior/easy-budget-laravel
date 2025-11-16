{{-- layouts/public.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Easy Budget - Sistema completo de orçamentos e gestão comercial. Crie orçamentos profissionais, controle estoque e gerencie clientes.">
    <meta name="keywords" content="orçamentos, gestão comercial, controle de estoque, faturas, QR code">
    <meta name="author" content="Easy Budget">
    
    <title>@yield('title', 'Easy Budget - Sistema de Orçamentos')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="{{ asset('assets/css/public.css') }}" rel="stylesheet">
    
    @stack('styles')
</head>
<body class="d-flex flex-column h-100">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="{{ route('home.index') }}">
                <i class="bi bi-calculator me-2"></i>Easy Budget
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home.index') }}">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home.features') }}">Recursos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home.pricing') }}">Preços</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home.about') }}">Sobre</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home.contact') }}">Contato</a>
                    </li>
                    @auth
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white px-3 ms-2" href="{{ route('provider.dashboard') }}">
                                Dashboard
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Entrar</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white px-3 ms-2" href="{{ route('register') }}">
                                Cadastrar-se
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-shrink-0" style="margin-top: 76px;">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-calculator me-2"></i>Easy Budget
                    </h5>
                    <p class="text-muted">
                        Sistema completo de orçamentos e gestão comercial para empresas que querem crescer.
                    </p>
                    <div class="social-links">
                        <a href="#" class="text-muted me-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-muted me-3"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-muted me-3"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 mb-4">
                    <h6 class="fw-bold mb-3">Recursos</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('home.features') }}" class="text-muted text-decoration-none">Funcionalidades</a></li>
                        <li><a href="{{ route('home.pricing') }}" class="text-muted text-decoration-none">Preços</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">API</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Integrações</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 mb-4">
                    <h6 class="fw-bold mb-3">Empresa</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('home.about') }}" class="text-muted text-decoration-none">Sobre</a></li>
                        <li><a href="{{ route('home.contact') }}" class="text-muted text-decoration-none">Contato</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Blog</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Carreiras</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 mb-4">
                    <h6 class="fw-bold mb-3">Suporte</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">Ajuda</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Documentação</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Status</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Termos de Uso</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 mb-4">
                    <h6 class="fw-bold mb-3">Legal</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">Privacidade</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Termos</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Cookies</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">LGPD</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-secondary">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">
                        &copy; {{ date('Y') }} Easy Budget. Todos os direitos reservados.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        Feito com <i class="bi bi-heart-fill text-danger"></i> para empresas brasileiras
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="{{ asset('assets/js/public.js') }}"></script>
    
    @stack('scripts')
</body>
</html>