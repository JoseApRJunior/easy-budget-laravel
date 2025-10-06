{{-- partials/shared/footer.blade.php --}}
<footer class="footer mt-auto py-2 d-print-none">
    <div class="container">
        <div class="row gy-4">
            <div class="col-md-4">
                <div class="footer-brand mb-3">
                    <img src="{{ asset( 'assets/img/logo.png' ) }}" alt="Easy Budget Logo" class="logo-img me-2"
                        role="img" aria-label="Easy Budget Logo">
                    <span class="logo-text">Easy Budget</span>
                </div>
                <p class="small mb-0">
                    Simplificando a gestão financeira para prestadores de serviços.
                </p>
            </div>

            <div class="col-6 col-md-4">
                <h5 class="mb-3">Suporte</h5>
                <ul class="list-unstyled footer-links small">
                    <li class="mb-2">
                        <a href="mailto:suporte@easybudget.com.br" class="footer-link">
                            <i class="bi bi-envelope me-2"></i>Fale Conosco
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="{{ route( 'support' ) }}" class="footer-link">
                            <i class="bi bi-ticket me-2"></i>Abrir Chamado
                        </a>
                    </li>
                    <li>
                        <a href="{{ route( 'support' ) }}" class="footer-link">
                            <i class="bi bi-question-circle me-2"></i>Central de Ajuda
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-6 col-md-4">
                <h5 class="mb-3">Conecte-se</h5>
                <div class="social-links mb-3">
                    <a href="https://www.facebook.com/jrwebdevelopment.2025" aria-label="Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="https://www.instagram.com/jrwebdevelopment.2025" aria-label="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <a href="https://www.linkedin.com/company/jrwebdevelopment.2025" aria-label="LinkedIn">
                        <i class="bi bi-linkedin"></i>
                    </a>
                </div>
                <p class="small mb-0">
                    &copy; {{ date( 'Y' ) }} Jr Web Development. Todos os direitos reservados.
                </p>
            </div>
        </div>
    </div>
</footer>
