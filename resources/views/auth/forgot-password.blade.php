@extends('layouts.app')

@section('title', 'Recuperar Senha')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <x-ui.card class="border-0 shadow-lg">
                    <div class="p-4">
                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle p-3 mb-3 text-primary">
                                <i class="bi bi-envelope-exclamation display-6"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-2">Esqueceu a senha?</h4>
                            <p class="text-muted small">
                                Digite seu email e enviaremos um link para redefinir sua senha.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf

                            <div class="mb-4">
                                <x-ui.form.input type="email" name="email" label="Email" placeholder="seu@email.com" required :value="old('email')" autofocus />
                            </div>

                            <div class="d-grid gap-3">
                                <x-ui.button type="submit" variant="primary" size="lg" icon="envelope" label="Enviar Link de Recuperação" />
                                
                                <x-ui.button href="{{ route('login') }}" variant="secondary" outline icon="arrow-left" label="Voltar ao Login" />
                            </div>
                        </form>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
@endsection
