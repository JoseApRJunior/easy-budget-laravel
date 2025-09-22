@extends( 'layouts.app' )

@section( 'content' )
<!-- Banner Principal com gradiente e animação -->
<section id="home" class="hero-section text-center position-relative mt-5">
  <div class="hero-overlay"></div>
  <div class="main-container position-relative">
    <!-- Alerta de teste -->
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <strong>Ambiente de Testes!</strong> Os dados podem ser resetados a qualquer momento. Não utilize dados
      reais.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <div class="hero-content">
      <h1 class="display-4 fw-bold mb-3">Bem-vindo ao Easy Budget</h1>
      <p class="lead mb-4">Transforme a gestão de seus serviços com nossas soluções diversificadas e inovadoras.
      </p>
      <button id="conhecaPlanos" class="btn btn-primary btn-lg pulse-button">
        <i class="bi bi-arrow-down-circle me-2"></i>Conheça nossos planos
      </button>
    </div>
  </div>
</section>

<!-- Seção de Planos -->
<section id="plans" class="py-5">
  <div class="main-container">
    <div class="section-header text-center mb-5">
      <h2 class="display-6 fw-bold">Escolha o Plano Perfeito para Você</h2>
      <p class="small-text">Selecione o plano que melhor atende às suas necessidades</p>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
      @foreach ( $plans as $plan )
      <div class="col">
        <div class="card h-100 shadow-sm hover-card">
          <div class="card-body d-flex flex-column">
            <div class="text-center mb-4">
              @if($plan['slug'] == 'free')
              <i class="bi bi-rocket display-6 text-primary mb-2"></i>
              @elseif($plan['slug'] == 'basic')
              <i class="bi bi-star display-6 text-success mb-2"></i>
              @else
              <i class="bi bi-gem display-6 text-info mb-2"></i>
              @endif
              <h3 class="card-title h4">{{ $plan['name'] }}</h3>
              <div class="pricing-header">
                <span class="currency">R$</span>
                <span class="price">{{ number_format($plan['price'], 0, '', '.') }}</span>
                <span class="period">/mês</span>
              </div>
            </div>

            <p class="card-text small-text mb-4">{{ $plan['description'] }}</p>

            <ul class="feature-list list-unstyled mb-4">
              @foreach($plan['features'] as $feature)
              <li class="mb-2">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                {{ $feature }}
              </li>
              @endforeach
            </ul>

            <button type="button" class="btn btn-outline-primary btn-lg mt-auto w-100 select-plan"
              data-plan="{{ $plan['name'] }}" data-target="#preCadastroForm">
              <i class="bi bi-arrow-right-circle me-2"></i>Selecionar
            </button>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    <!-- Formulário de Pré-Cadastro -->
    <div id="preCadastroForm" class="card shadow-lg border-0 rounded-3">
      <div class="card-header py-3">
        <h5 class="card-title text-center mb-0">
          <i class="bi bi-person-plus me-2"></i>Pré-Cadastro
        </h5>
      </div>
      <div class="card-body p-4">
        <form action="{{ route( 'register' ) }}" method="POST" id="preRegisterForm" class="needs-validation" novalidate>
          @csrf
          <div class="row g-3">
            <div class="col-md-3">
              <label for="plan" class="form-label">Plano Selecionado</label>
              <select class="form-select" id="planSelect" name="plan" required>
                @foreach ( $plans as $plan )
                <option value="{{ $plan[ 'slug' ] }}" {{ $plan[ 'slug' ] != 'free' ? 'disabled' : '' }}>
                  {{ $plan[ 'name' ] }} - R$ {{ number_format( $plan[ 'price' ], 2, ',', '.' ) }}
                  {{ $plan[ 'slug' ] != 'free' ? ' - em desenvolvimento' : '' }}
                </option>
                @endforeach
              </select>
              {!! $errors->first( 'plan', '<div class="invalid-feedback">' . $errors->first( 'plan' ) . '</div>' ) !!}
            </div>

            <div class="col-md-3">
              <label for="first_name" class="form-label">Nome</label>
              <input type="text" class="form-control @error( 'first_name' ) is-invalid @enderror" id="first_name"
                name="first_name" value="{{ old( 'first_name' ) }}" required />
              {!! $errors->first( 'first_name', '<div class="invalid-feedback">' . $errors->first( 'first_name' ) . '
              </div>' ) !!}
            </div>

            <div class="col-md-3">
              <label for="last_name" class="form-label">Sobrenome</label>
              <input type="text" class="form-control @error( 'last_name' ) is-invalid @enderror" id="last_name"
                name="last_name" value="{{ old( 'last_name' ) }}" required />
              {!! $errors->first( 'last_name', '<div class="invalid-feedback">' . $errors->first( 'last_name' ) . '
              </div>' ) !!}
            </div>

            <div class="col-md-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control @error( 'email' ) is-invalid @enderror" id="email" name="email"
                value="{{ old( 'email' ) }}" required />
              {!! $errors->first( 'email', '<div class="invalid-feedback">' . $errors->first( 'email' ) . '</div>' ) !!}
            </div>

            <div class="col-md-3">
              <label for="phone" class="form-label">Telefone</label>
              <input type="tel" class="form-control @error( 'phone' ) is-invalid @enderror" id="phone" name="phone"
                value="{{ old( 'phone' ) }}" required />
              {!! $errors->first( 'phone', '<div class="invalid-feedback">' . $errors->first( 'phone' ) . '</div>' ) !!}
            </div>

            <div class="col-md-3">
              <label for="password" class="form-label">Senha</label>
              <div class="password-container">
                <input type="password" class="form-control @error( 'password' ) is-invalid @enderror" id="password"
                  name="password" required />
                <button type="button" class="password-toggle btn btn-sm btn-outline-secondary" data-input="password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              {!! $errors->first( 'password', '<div class="invalid-feedback">' . $errors->first( 'password' ) . '</div>'
              ) !!}
              <div class="password-rules" style="display:none;">
                <ul>
                  <li>
                    <i class="fas fa-check-circle" style="color: #ccc;"></i>
                    Pelo menos 6 caracteres
                  </li>
                  <li>
                    <i class="fas fa-check-circle" style="color: #ccc;"></i>
                    Letras minúsculas (a-z)
                  </li>
                  <li>
                    <i class="fas fa-check-circle" style="color: #ccc;"></i>
                    Letras maiúsculas (A-Z)
                  </li>
                  <li>
                    <i class="fas fa-check-circle" style="color: #ccc;"></i>
                    Números (0-9)
                  </li>
                  <li>
                    <i class="fas fa-check-circle" style="color: #ccc;"></i>
                    Caracteres especiais (@#$!%*?&)
                  </li>
                </ul>
              </div>
            </div>

            <div class="col-md-3">
              <label for="confirm_password" class="form-label">Confirmar Senha</label>
              <div class="password-container">
                <input type="password" class="form-control @error( 'confirm_password' ) is-invalid @enderror"
                  id="confirm_password" name="confirm_password" required />
                <button type="button" class="password-toggle btn btn-sm btn-outline-secondary"
                  data-input="confirm_password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              {!! $errors->first( 'confirm_password', '<div class="invalid-feedback">' . $errors->first(
                'confirm_password' ) . '</div>' ) !!}
            </div>

            <div class="col-md-3">
              <label class="form-label">&nbsp;</label>
              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-send me-2"></i>Enviar Pré-Cadastro
              </button>
            </div>

            <div class="mt-4 text-center">
              <div class="form-check mb-3">
                <label class="form-check-label" for="terms_accepted">
                  <input class="form-check-input @error( 'terms_accepted' ) is-invalid @enderror" type="checkbox"
                    id="terms_accepted" name="terms_accepted" required />
                  Eu li e aceito os <a href="{{ route( 'legal.terms' ) }}" target="_blank">Termos de
                    Serviço</a> e a <a href="{{ route( 'legal.privacy' ) }}" target="_blank">Política
                    de Privacidade</a>.
                </label>
              </div>
              {!! $errors->first( 'terms_accepted', '<div class="invalid-feedback">' . $errors->first( 'terms_accepted'
                ) . '</div>' ) !!}
              <small class="small-text d-block mb-3">Ao se cadastrar, você concorda em receber
                atualizações sobre nossos serviços por e-mail. Você pode cancelar a qualquer
                momento.</small>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection