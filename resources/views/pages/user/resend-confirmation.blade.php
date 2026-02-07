@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid d-flex align-items-center justify-content-center min-vh-75 py-1 py-md-5">
        <div class="row justify-content-center w-100">
            <div class="col-12 col-sm-11 col-md-9 col-lg-7 col-xl-5">
                <div class="card border-0 shadow-lg rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-primary">Reenviar link de confirmação</h2>
                            <p class="small-text small">Insira seu e-mail para receber o link
                            </p>
                        </div>

                        @if ( session( 'status' ) )
                            <div class="alert alert-success alert-dismissible fade show py-1 small" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                {{ session( 'status' ) }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <form action="{{ route( 'verification.send' ) }}" method="post" id="resendConfirmationForm">
                            @csrf

                            <div class="form-floating mb-4">
                                <input type="email" class="form-control @error( 'email' ) is-invalid @enderror" id="email"
                                    name="email" placeholder="nome@exemplo.com" required value="{{ old( 'email' ) }}">
                                <label for="email">Endereço de E-mail</label>
                                @error( 'email' )
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-envelope-fill me-2"></i>Enviar Link
                                </button>
                            </div>
                        </form>

                        <div class="mt-4 text-center">
                            <a href="{{ route( 'login' ) }}" class="text-decoration-none small small-text">

                                <i class="bi bi-arrow-left  me-1"></i>Voltar para o login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
