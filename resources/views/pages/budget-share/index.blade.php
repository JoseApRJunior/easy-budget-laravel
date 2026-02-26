@extends('layouts.app')

@section('title', 'Compartilhamentos de Orçamentos')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Compartilhamentos de Orçamentos"
            icon="share"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Orçamentos' => route('provider.budgets.index'),
                'Compartilhamentos' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button :href="route('provider.budgets.index')" variant="secondary" outline icon="arrow-left" label="Voltar aos Orçamentos" feature="budgets" />
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12">
                <x-resource.resource-list-card
                    title="Lista de Compartilhamentos"
                    icon="list-ul"
                    :total="$shares->total()"
                >
                    <x-resource.resource-table :headers="['ID', 'Orçamento', 'Destinatário', 'Token', 'Expiração', 'Status', 'Criado em', 'Ações']">
                        @forelse($shares as $share)
                            <x-resource.table-row>
                                <x-resource.table-cell>{{ $share->id }}</x-resource.table-cell>
                                <x-resource.table-cell>
                                    <a href="{{ route('provider.budgets.show', $share->budget->code) }}" target="_blank" class="text-decoration-none fw-bold">
                                        #{{ $share->budget->code }}
                                    </a>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    <div>{{ $share->recipient_name }}</div>
                                    <small class="text-muted">{{ $share->recipient_email }}</small>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    <div class="d-flex align-items-center gap-2">
                                        <code class="bg-light px-2 py-1 rounded">{{ substr($share->token, 0, 8) }}...</code>
                                        <button class="btn btn-sm btn-link text-secondary p-0 copy-token" data-token="{{ $share->token }}" title="Copiar token">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    {{ $share->expires_at ? $share->expires_at->format('d/m/Y H:i') : 'Nunca' }}
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    @if($share->expires_at && \Carbon\Carbon::parse($share->expires_at)->isPast())
                                        <span class="badge bg-danger">Expirado</span>
                                    @else
                                        <span class="badge bg-success">Ativo</span>
                                    @endif
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    {{ $share->created_at->format('d/m/Y H:i') }}
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    <x-resource.action-buttons>
                                        <x-ui.button :href="route('provider.budgets.shares.show', $share)" variant="primary" outline size="sm" icon="eye" title="Ver detalhes" feature="budgets" />

                                        <x-ui.button :href="route('budgets.public.shared.view', $share->token)" target="_blank" variant="info" outline size="sm" icon="box-arrow-up-right" title="Ver link público" feature="budgets" />

                                        @if(!($share->expires_at && \Carbon\Carbon::parse($share->expires_at)->isPast()))
                                            <x-ui.button
                                                type="button"
                                                variant="warning"
                                                outline
                                                size="sm"
                                                icon="slash-circle"
                                                title="Revogar acesso"
                                                data-bs-toggle="modal"
                                                data-bs-target="#revokeModal"
                                                data-action-url="{{ route('provider.budgets.shares.revoke', $share) }}"
                                                data-item-name="{{ $share->budget->code }}"
                                                feature="budgets"
                                            />
                                        @endif

                                        <x-ui.button
                                            type="button"
                                            variant="danger"
                                            outline
                                            size="sm"
                                            icon="trash"
                                            title="Excluir"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal"
                                            data-delete-url="{{ route('provider.budgets.shares.destroy', $share) }}"
                                            data-item-name="{{ $share->budget->code }}"
                                            feature="budgets"
                                        />
                                    </x-resource.action-buttons>
                                </x-resource.table-cell>
                            </x-resource.table-row>
                        @empty
                            <x-resource.table-row>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-share display-4 d-block mb-3"></i>
                                    Nenhum compartilhamento encontrado
                                </td>
                            </x-resource.table-row>
                        @endforelse
                    </x-resource.resource-table>

                    @if($shares->hasPages())
                        <div class="mt-4">
                            {{ $shares->links() }}
                        </div>
                    @endif
                </x-resource.resource-list-card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>

    <x-ui.confirm-modal
        id="revokeModal"
        title="Confirmar Revogação"
        message="Tem certeza que deseja revogar o compartilhamento do orçamento <strong id='revokeModalItemName'></strong>?"
        submessage="O link de acesso será invalidado e não poderá mais ser usado."
        confirmLabel="Revogar Compartilhamento"
        variant="warning"
        type="confirm"
        method="POST"
        resource="compartilhamento"
    />

    <x-ui.confirm-modal
        id="deleteModal"
        title="Confirmar Exclusão"
        message="Tem certeza que deseja excluir o compartilhamento do orçamento <strong id='deleteModalItemName'></strong>?"
        submessage="Esta ação não pode ser desfeita."
        confirmLabel="Excluir"
        variant="danger"
        type="delete"
        resource="compartilhamento"
    />
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
                icon.className = 'bi bi-check-lg text-success';

                setTimeout(() => {
                    icon.className = originalClass;
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
