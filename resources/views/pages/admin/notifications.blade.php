@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4"><i class="bi bi-bell me-2"></i>Configurações de Notificação</h2>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-envelope me-2"></i>Notificações por Email</h5>
                            </div>
                            <div class="card-body">
                                <form id="notificationForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label">Emails para Alertas</label>
                                        <div id="emailInputs">
                                            <div class="input-group mb-2">
                                                <input type="email" class="form-control" name="emails[]"
                                                    placeholder="admin@easy-budget.com" required>
                                                <button type="button" class="btn btn-outline-danger"
                                                    onclick="removeEmailInput(this)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="addEmailInput()">
                                            <i class="bi bi-plus me-1"></i>Adicionar Email
                                        </button>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Telefones para SMS (Alertas Críticos)</label>
                                        <div id="phoneInputs">
                                            <div class="input-group mb-2">
                                                <input type="tel" class="form-control" name="phones[]"
                                                    placeholder="+5511999999999">
                                                <button type="button" class="btn btn-outline-danger"
                                                    onclick="removePhoneInput(this)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="addPhoneInput()">
                                            <i class="bi bi-plus me-1"></i>Adicionar Telefone
                                        </button>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-check me-1"></i>Salvar Configurações
                                        </button>
                                        <button type="button" class="btn btn-outline-info" onclick="testEmail()">
                                            <i class="bi bi-send me-1"></i>Testar Email
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Tipos de Alerta</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-danger">
                                    <strong>🚨 CRÍTICO</strong><br>
                                    <small>Tempo > 150ms ou Taxa < 90%</small><br>
                                            <small>📧 Email + 📱 SMS</small>
                                </div>
                                <div class="alert alert-warning">
                                    <strong>⚠️ ATENÇÃO</strong><br>
                                    <small>Tempo > 100ms ou Taxa < 95%</small><br>
                                            <small>📧 Email apenas</small>
                                </div>
                                <div class="alert alert-info">
                                    <strong>ℹ️ INFORMATIVO</strong><br>
                                    <small>Eventos do sistema</small><br>
                                    <small>📧 Email apenas</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        function addEmailInput() {
            const container = document.getElementById( 'emailInputs' );
            const div = document.createElement( 'div' );
            div.className = 'input-group mb-2';
            div.innerHTML = `
                <input type="email" class="form-control" name="emails[]" placeholder="email@exemplo.com" required>
                <button type="button" class="btn btn-outline-danger" onclick="removeEmailInput(this)">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            container.appendChild( div );
        }

        function removeEmailInput( button ) {
            const container = document.getElementById( 'emailInputs' );
            if ( container.children.length > 1 ) {
                button.parentElement.remove();
            }
        }

        function addPhoneInput() {
            const container = document.getElementById( 'phoneInputs' );
            const div = document.createElement( 'div' );
            div.className = 'input-group mb-2';
            div.innerHTML = `
                <input type="tel" class="form-control" name="phones[]" placeholder="+5511999999999">
                <button type="button" class="btn btn-outline-danger" onclick="removePhoneInput(this)">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            container.appendChild( div );
        }

        function removePhoneInput( button ) {
            button.parentElement.remove();
        }

        function testEmail() {
            fetch( '{{ url( '/admin/notifications/test-email' ) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            } )
                .then( response => response.json() )
                .then( data => {
                    alert( data.success ? '✅ ' + data.message : '❌ ' + data.message );
                } );
        }

        document.getElementById( 'notificationForm' ).addEventListener( 'submit', function ( e ) {
            e.preventDefault();
            const formData = new FormData( this );

            fetch( '{{ url( '/admin/notifications/save' ) }}', {
                method: 'POST',
                body: formData
            } )
                .then( response => response.json() )
                .then( data => {
                    alert( data.success ? '✅ ' + data.message : '❌ ' + data.message );
                } );
        } );
    </script>
@endpush
