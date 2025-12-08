@extends( 'layouts.app' )

@section( 'title', 'Nova Categoria' )

@section( 'content' )
    <div class="container-fluid py-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-tag-plus me-2"></i>Nova Categoria
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'categories.index' ) }}">Categorias</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Nova</li>
                </ol>
            </nav>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @if( $errors->any() )
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach( $errors->all() as $error )
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route( 'categories.store' ) }}" method="POST">
                    @csrf
                    <input type="hidden" id="tenantId" value="{{ optional( auth()->user() )->tenant_id }}">
                    <div class="row g-4">
                        <div class="col-md-12">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control @error( 'name' ) is-invalid @enderror" id="name"
                                    name="name" placeholder="Nome da Categoria" value="{{ old( 'name' ) }}" required>
                                <label for="name">Nome da Categoria *</label>
                                @error( 'name' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-floating mb-3">
                                <select class="form-control" id="parent_id" name="parent_id">
                                    <option value="">Sem categoria pai</option>
                                    @foreach( ( $parents ?? collect() ) as $p )
                                        <option value="{{ $p->id }}" {{ (string) old( 'parent_id' ) === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                                <label for="parent_id">Categoria Pai (opcional)</label>
                            </div>
                            <div class="form-floating">
                                <input type="text" class="form-control" id="slugPreview" name="slugPreview"
                                    value="{{ Str::slug( old( 'name' ) ) }}" placeholder="slug" disabled>
                                <label for="slugPreview">Slug (gerado automaticamente)</label>
                            </div>
                            <div class="form-text" id="slugStatus"></div>
                            <input type="hidden" name="is_active" value="0">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old( 'is_active', ( $defaults[ 'is_active' ] ?? true ) ) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Ativo</label>
                                @error( 'is_active' )
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            <a href="{{ route( 'categories.index' ) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancelar
                            </a>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Criar Categoria
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
<script>
    ( function () {
        var nameInput = document.getElementById( 'name' );
        var slugInput = document.getElementById( 'slugPreview' );
        var statusEl = document.getElementById( 'slugStatus' );
        var submitBtn = document.querySelector( 'form button[type="submit"]' );
        var tenantIdEl = document.getElementById( 'tenantId' );
        var tenantId = tenantIdEl && tenantIdEl.value ? parseInt( tenantIdEl.value ) : null;
        var formEl = document.querySelector( 'form' );
        var lastCheck = {
            slug: '',
            attached: false,
            exists: false
        };
        var isAdmin = false;
        @role( 'admin' )
        isAdmin = true;
        @endrole

        function slugify( text ) {
            return text.toString().normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' )
                .toLowerCase()
                .replace( /[^a-z0-9\s-]/g, '' )
                .trim()
                .replace( /\s+/g, '-' )
                .replace( /-+/g, '-' );
        }

        function checkSlug( slug ) {
            var url = window.location.origin + '/categories/ajax/check-slug' + '?slug=' + encodeURIComponent( slug ) + ( tenantId ? '&tenant_id=' + tenantId : '' );
            fetch( url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            } )
                .then( function ( r ) {
                    return r.json();
                } )
                .then( function ( data ) {
                    lastCheck = {
                        slug: data.slug,
                        attached: !!data.attached,
                        exists: !!data.exists
                    };
                    if ( data.exists ) {
                        statusEl.textContent = 'Este nome já está em uso.';
                        statusEl.className = 'form-text text-danger';
                        submitBtn.disabled = true;
                        nameInput.classList.add( 'is-invalid' );
                    } else {
                        statusEl.textContent = 'Slug disponível.';
                        statusEl.className = 'form-text text-muted';
                        submitBtn.disabled = false;
                        nameInput.classList.remove( 'is-invalid' );
                    }
                } )
                .catch( function () {
                    statusEl.textContent = '';
                    statusEl.className = 'form-text';
                    submitBtn.disabled = false;
                    nameInput.classList.remove( 'is-invalid' );
                } );
        }
        if ( nameInput && slugInput ) {
            nameInput.addEventListener( 'input', function () {
                slugInput.value = slugify( nameInput.value || '' );
                var s = slugInput.value;
                if ( s ) {
                    checkSlug( s );
                }
            } );
            if ( nameInput.value ) {
                checkSlug( slugify( nameInput.value ) );
            }

            if ( formEl ) {
                formEl.addEventListener( 'submit', function ( e ) {
                    var currentSlug = slugify( nameInput.value || '' );
                    if ( !currentSlug ) {
                        return;
                    }
                    if ( lastCheck.slug !== currentSlug ) {
                        e.preventDefault();
                        checkSlug( currentSlug );
                        setTimeout( function () {
                            if ( lastCheck.attached ) {
                                submitBtn.disabled = true;
                                return;
                            }
                            formEl.submit();
                        }, 200 );
                    } else if ( lastCheck.attached ) {
                        e.preventDefault();
                        submitBtn.disabled = true;
                    }
                } );
            }
        }
    } )();
</script>
@endpush
