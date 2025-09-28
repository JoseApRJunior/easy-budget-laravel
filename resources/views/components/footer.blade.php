<footer class="bg-light py-4 mt-auto">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold">
                    <i class="bi bi-graph-up me-2"></i>Easy Budget
                </h6>
                <p class="text-muted mb-0">
                    Sistema completo para gestão de orçamentos e serviços.
                </p>
            </div>
            <div class="col-md-3">
                <h6 class="fw-bold">Links Úteis</h6>
                <ul class="list-unstyled">
                    <li><a href="{{ route( 'about' ) }}" class="text-muted text-decoration-none">Sobre</a></li>
                    <li><a href="{{ route( 'support' ) }}" class="text-muted text-decoration-none">Suporte</a></li>
                    <li><a href="/terms-of-service" class="text-muted text-decoration-none">Termos de Serviço</a></li>
                    <li><a href="/privacy-policy" class="text-muted text-decoration-none">Política de Privacidade</a>
                    </li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="fw-bold">Contato</h6>
                <ul class="list-unstyled text-muted">
                    <li><i class="bi bi-envelope me-2"></i>suporte@easybudget.com.br</li>
                    <li><i class="bi bi-clock me-2"></i>Atendimento 24/7</li>
                </ul>
            </div>
        </div>
        <hr class="my-3">
        <div class="row">
            <div class="col-md-6">
                <small class="text-muted">
                    © {{ date( 'Y' ) }} Easy Budget. Todos os direitos reservados.
                </small>
            </div>
            <div class="col-md-6 text-md-end">
                <small class="text-muted">
                    Desenvolvido com <i class="bi bi-heart-fill text-danger"></i> para facilitar sua gestão
                </small>
            </div>
        </div>
    </div>
</footer>
