{{-- resources/views/auth/register.blade.php --}}
{{-- Página de Registro integrada ao tema do sistema --}}

@extends( 'layouts.app' )

@section( 'content' )
   {{-- Breadcrumbs --}}
   @section( 'breadcrumbs' )
       <li class="breadcrumb-item active d-flex align-items-center">
           <i class="bi bi-person-plus me-2"></i>
           Cadastro
       </li>
   @endsection

   {{-- Register Section --}}
   <div class="container mt-5">
       <div class="row justify-content-center">
           <div class="col-xl-8 col-lg-9 col-md-10">
               <div class="text-center mb-5">
                   <div class="d-inline-flex align-items-center justify-content-center w-100 mb-4" style="height: 100px;">
                       <div class="bg-success bg-opacity-10 p-4 rounded-circle">
                           <i class="bi bi-person-plus text-success" style="font-size: 4rem;"></i>
                       </div>
                   </div>
                   <h1 class="display-5 fw-bold mb-3">Criar nova conta</h1>
                   <p class="lead">Junte-se ao Easy Budget e comece a gerenciar seus orçamentos</p>
               </div>

               {{-- Register Form --}}
               <div class="card shadow-sm mb-4">
                   <div class="card-body p-5">
                       <form method="POST" action="{{ route( 'register' ) }}">
                           @csrf

                           {{-- Name Field --}}
                           <div class="row mb-4">
                               <div class="col-md-6">
                                   <label for="first_name" class="form-label fw-semibold">
                                       <i class="bi bi-person me-2"></i>Nome
                                   </label>
                                   <input id="first_name" name="first_name" type="text"
                                       value="{{ old( 'first_name' ) }}" required autofocus autocomplete="given-name"
                                       class="form-control @error('first_name') is-invalid @enderror"
                                       placeholder="Seu nome">
                                   @error( 'first_name' )
                                       <div class="invalid-feedback">{{ $message }}</div>
                                   @enderror
                               </div>
                               <div class="col-md-6">
                                   <label for="last_name" class="form-label fw-semibold">
                                       <i class="bi bi-person me-2"></i>Sobrenome
                                   </label>
                                   <input id="last_name" name="last_name" type="text"
                                       value="{{ old( 'last_name' ) }}" required autocomplete="family-name"
                                       class="form-control @error('last_name') is-invalid @enderror"
                                       placeholder="Seu sobrenome">
                                   @error( 'last_name' )
                                       <div class="invalid-feedback">{{ $message }}</div>
                                   @enderror
                               </div>
                           </div>

                           {{-- Email Field --}}
                           <div class="mb-4">
                               <label for="email" class="form-label fw-semibold">
                                   <i class="bi bi-envelope me-2"></i>E-mail
                               </label>
                               <div class="input-group">
                                   <span class="input-group-text">
                                       <i class="bi bi-envelope"></i>
                                   </span>
                                   <input id="email" name="email" type="email"
                                       value="{{ old( 'email' ) }}" required autocomplete="username"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="seu@email.com">
                                   @error( 'email' )
                                       <div class="invalid-feedback">{{ $message }}</div>
                                   @enderror
                               </div>
                           </div>

                           {{-- Password Field --}}
                           <div class="mb-4">
                               <label for="password" class="form-label fw-semibold">
                                   <i class="bi bi-key me-2"></i>Senha
                               </label>
                               <div class="input-group">
                                   <span class="input-group-text">
                                       <i class="bi bi-key"></i>
                                   </span>
                                   <input id="password" name="password" type="password" required
                                       autocomplete="new-password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="••••••••">
                                   <span class="input-group-text password-toggle" style="cursor: pointer;" data-input="password">
                                       <i class="bi bi-eye"></i>
                                   </span>
                                   @error( 'password' )
                                       <div class="invalid-feedback">{{ $message }}</div>
                                   @enderror
                               </div>
                           </div>

                           {{-- Confirm Password Field --}}
                           <div class="mb-4">
                               <label for="password_confirmation" class="form-label fw-semibold">
                                   <i class="bi bi-shield-check me-2"></i>Confirmar Senha
                               </label>
                               <div class="input-group">
                                   <span class="input-group-text">
                                       <i class="bi bi-shield-check"></i>
                                   </span>
                                   <input id="password_confirmation" name="password_confirmation" type="password" required
                                       autocomplete="new-password"
                                       class="form-control @error('password_confirmation') is-invalid @enderror"
                                       placeholder="••••••••">
                                   @error( 'password_confirmation' )
                                       <div class="invalid-feedback">{{ $message }}</div>
                                   @enderror
                               </div>
                           </div>

                           {{-- Terms and Conditions --}}
                           <div class="mb-4">
                               <div class="form-check">
                                   <input id="terms" name="terms" type="checkbox" required
                                       class="form-check-input @error('terms') is-invalid @enderror">
                                   <label for="terms" class="form-check-label">
                                       Aceito os <a href="/terms-of-service" target="_blank" class="text-decoration-none">Termos de Serviço</a>
                                       e a <a href="/privacy-policy" target="_blank" class="text-decoration-none">Política de Privacidade</a>
                                   </label>
                                   @error( 'terms' )
                                       <div class="invalid-feedback">{{ $message }}</div>
                                   @enderror
                               </div>
                           </div>

                           {{-- Submit Button --}}
                           <div class="d-grid mb-4">
                               <button type="submit" class="btn btn-success btn-lg">
                                   <i class="bi bi-person-plus me-2"></i>
                                   Criar Conta
                               </button>
                           </div>

                           {{-- Login Link --}}
                           <div class="text-center">
                               <p class="mb-0">
                                   Já tem uma conta?
                                   <a href="{{ route( 'login' ) }}" class="text-decoration-none">
                                       <i class="bi bi-box-arrow-in-right me-1"></i>
                                       Fazer login
                                   </a>
                               </p>
                           </div>
                       </form>
                   </div>
               </div>
           </div>
       </div>
   </div>
@endsection

{{-- Custom Scripts --}}
@section( 'scripts' )
   <script>
       // Password visibility toggle
       document.addEventListener('DOMContentLoaded', function () {
           const passwordToggles = document.querySelectorAll('.password-toggle');

           passwordToggles.forEach(toggle => {
               toggle.addEventListener('click', function (e) {
                   e.preventDefault();

                   // Buscar o input mais próximo (irmão anterior)
                   const input = this.previousElementSibling;

                   if (input && input.tagName === 'INPUT') {
                       const icon = this.querySelector('i');

                       if (input.getAttribute('type') === 'password') {
                           input.setAttribute('type', 'text');
                           icon.classList.remove('bi-eye');
                           icon.classList.add('bi-eye-slash');
                       } else {
                           input.setAttribute('type', 'password');
                           icon.classList.remove('bi-eye-slash');
                           icon.classList.add('bi-eye');
                       }
                   }
               });
           });
       });
   </script>
@endsection
