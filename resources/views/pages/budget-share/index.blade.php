@extends( 'layouts.app' )

@section( 'title', 'Compartilhamentos de Orçamentos' )

@section( 'content' )
    <div class="container-fluid py-4">
        <x-page-header
            title="Compartilhamentos de Orçamentos"
            icon="share"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Orçamentos' => route('provider.budgets.index'),
                'Compartilhamentos' => '#'
            ]">
            <x-button :href="route('provider.budgets.index')" variant="secondary" outline icon="arrow-left" label="Voltar aos Orçamentos" />
        </x-page-header>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Lista de Compartilhamentos</h3>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Orçamento</th>
                                        <th>Cliente</th>
                                        <th>Email</th>
                                        <th>Token</th>
                                        <th>Expiração</th>
                                        <th>Status</th>
                                        <th>Criado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($shares as $share)
                                        <tr>
                                            <td>{{ $share->id }}</td>
                                            <td>
                                                <a href="{{ route('provider.budgets.show', $share->budget->code) }}" target="_blank">
                                                    #{{ $share->budget->code }}
                                                </a>
                                            </td>
                                            <td>{{ $share->budget->customer->name ?? 'N/A' }}</td>
                                            <td>{{ $share->email }}</td>
                                            <td>
                                                <x-share-token :token="substr($share->share_token, 0, 8) . '...'" :show-copy="false" />
                                                <button class="btn btn-sm btn-outline-secondary copy-token" data-token="{{ $share->share_token }}" title="Copiar token completo">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </td>
                                            <td>{{ $share->expires_at ? $share->expires_at->format('d/m/Y H:i') : 'Nunca' }}</td>
                                            <td>
                                                <x-share-status-badge :share="$share" />
                                            </td>
                                            <td>{{ $share->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('provider.budgets.shares.show', $share) }}" class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <a href="{{ route('provider.budgets.public.shared.view', $share->share_token) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Ver link público">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>

                                                    @if($share->is_active)
                                                        <form action="{{ route('provider.budgets.shares.revoke', $share) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Revogar acesso" onclick="return confirm('Tem certeza que deseja revogar este compartilhamento?')">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <form action="{{ route('provider.budgets.shares.destroy', $share) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este compartilhamento?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">Nenhum compartilhamento encontrado</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($shares->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $shares->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copiar token para área de transferência
    document.querySelectorAll('.copy-token').forEach(button => {
        button.addEventListener('click', function() {
            const token = this.getAttribute('data-token');
            navigator.clipboard.writeText(token).then(() => {
                // Mudar ícone temporariamente
                const icon = this.querySelector('i');
                const originalClass = icon.className;
                icon.className = 'fas fa-check';
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-success');
                
                setTimeout(() => {
                    icon.className = originalClass;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline-secondary');
                }, 2000);
            }).catch(err => {
                console.error('Erro ao copiar token:', err);
                alert('Erro ao copiar token. Por favor, copie manualmente.');
            });
        });
    });
});
</script>
@endpush