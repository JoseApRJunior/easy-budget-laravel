@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid d-flex align-items-center justify-content-center min-vh-75 py-1 py-md-5">
        <div class="row justify-content-center w-100">
            <div class="col-12 col-sm-11 col-md-9 col-lg-7 col-xl-5">
                <div class="card border-0 shadow-lg rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-primary">
                                Alterar Senha
                            </h2>
                            <p class="small-test small">
                                Insira sua nova senha
                            </p>
                        </div>
                        <form action="{{ route( 'provider.change_password_store' ) }}" method="post"
                            id="changePasswordForm">
                            @csrf

                            <div class="form-floating password-container mb-3">
                                <input type="password"
                                    class="form-control @error( 'current_password' ) is-invalid @enderror"
                                    id="current_password" name="current_password" required />
                                <label for="current_password" class="form-label">
                                    Senha atual
                                </label>
                                <button type="button" class="password-toggle" data-input="current_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @error( 'current_password' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-floating password-container mb-3">
                                <input type="password" class="form-control @error( 'password' ) is-invalid @enderror"
                                    id="password" name="password" required />
                                <label for="password" class="form-label">
                                    Nova Senha
                                </label>
                                <button type="button" class="password-toggle" data-input="password">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @error( 'password' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-floating password-container">
                                <input type="password" class="form-control" id="password_confirmation"
                                    name="password_confirmation" required />
                                <label for="password_confirmation" class="form-label">
                                    Confirmar Nova Senha
                                </label>
                                <button type="button" class="password-toggle" data-input="password_confirmation">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">Alterar Senha</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            const passwordToggles = document.querySelectorAll( '.password-toggle' );

            passwordToggles.forEach( function ( toggle ) {
                toggle.addEventListener( 'click', function () {
                    const inputId = this.dataset.input;
                    const input = document.getElementById( inputId );
                    const icon = this.querySelector( 'i' );

                    if ( input.type === 'password' ) {
                        input.type = 'text';
                        icon.classList.remove( 'bi-eye' );
                        icon.classList.add( 'bi-eye-slash' );
                    } else {
                        input.type = 'password';
                        icon.classList.remove( 'bi-eye-slash' );
                        icon.classList.add( 'bi-eye' );
                    }
                } );
            } );
        } );
    </script>
@endpush
