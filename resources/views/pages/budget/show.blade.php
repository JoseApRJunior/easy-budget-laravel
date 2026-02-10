@extends('layouts.app')

@section('title', 'Detalhes do Orçamento')

@php
    $isSent = $budget->actionHistory()->whereIn('action', ['sent_and_reserved', 'sent'])->exists();
    $isDraft = $budget->status->value === 'draft';
    $sendModalTitle = ($isSent && !$isDraft) ? 'Reenviar Orçamento' : 'Enviar Orçamento';
    $sendModalLabel = ($isSent && !$isDraft) ? 'Reenviar' : 'Enviar';
@endphp

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Detalhes do Orçamento"
        icon="file-earmark-text"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Orçamentos' => route('provider.budgets.dashboard'),
            $budget->code => '#'
        ]">
        <p class="text-muted mb-0">Visualize as informações completas do orçamento</p>
    </x-layout.page-header>

    <x-layout.v-stack gap="4">
        <x-resource.resource-header-card
            :title="'Orçamento ' . $budget->code"
            :subtitle="'Criado em ' . $budget->created_at->format('d/m/Y')"
            :status-item="$budget"
            mb="mb-0">

            <x-slot:actions>
                <div class="d-flex gap-2">
                    <x-ui.button type="link" :href="route('provider.budgets.edit', $budget->code)"
                        variant="light" size="sm" icon="pencil" label="Editar" />
                    <x-ui.button type="link" :href="route('provider.budgets.print', ['code' => $budget->code, 'pdf' => true])"
                        variant="light" size="sm" icon="printer" label="Imprimir" target="_blank" />
                </div>
            </x-slot:actions>

            <x-resource.resource-header-section title="Informações do Cliente" icon="person-badge">
                <x-layout.grid-col size="col-md-4">
                    <x-resource.resource-info
                        title="Cliente"
                        :subtitle="$budget->customer->name ?? 'Não vinculado'"
                        icon="person" />
                </x-layout.grid-col>

                <x-layout.grid-col size="col-md-4">
                    <x-resource.resource-info
                        title="E-mail"
                        :subtitle="$budget->customer->contact->email_personal ?? 'Não informado'"
                        icon="envelope" />
                </x-layout.grid-col>

                <x-layout.grid-col size="col-md-4">
                    <x-resource.resource-info
                        title="Telefone"
                        :subtitle="$budget->customer->contact->phone_primary ?? 'Não informado'"
                        icon="telephone" />
                </x-layout.grid-col>
            </x-resource.resource-header-section>

            <x-resource.resource-header-divider />

            <x-resource.resource-header-section title="Resumo Financeiro" icon="cash-stack">
                <x-layout.grid-col size="col-md-3">
                    <x-resource.resource-info
                        title="Subtotal"
                        :subtitle="'R$ ' . \App\Helpers\CurrencyHelper::format($budget->services?->sum('total') ?? 0)"
                        icon="calculator" />
                </x-layout.grid-col>

                <x-layout.grid-col size="col-md-3">
                    <x-resource.resource-info
                        title="Desconto"
                        :subtitle="'R$ ' . \App\Helpers\CurrencyHelper::format($budget->discount)"
                        icon="percent"
                        class="{{ $budget->discount > 0 ? 'text-danger' : '' }}" />
                </x-layout.grid-col>

                <x-layout.grid-col size="col-md-3">
                    <x-resource.resource-info
                        title="Total"
                        :subtitle="'R$ ' . \App\Helpers\CurrencyHelper::format($budget->total)"
                        icon="currency-dollar"
                        class="fw-bold text-primary" />
                </x-layout.grid-col>

                <x-layout.grid-col size="col-md-3">
                    <x-resource.resource-info
                        title="Validade"
                        :subtitle="$budget->due_date ? $budget->due_date->format('d/m/Y') : 'Não informada'"
                        icon="calendar-event"
                        class="{{ $budget->due_date && $budget->due_date->isPast() ? 'text-danger' : '' }}" />
                </x-layout.grid-col>
            </x-resource.resource-header-section>

        {{-- Descrição e Observações --}}
        @if ($budget->description || $budget->payment_terms)
            <x-resource.resource-header-divider />
            <x-resource.resource-header-section title="Observações Adicionais" icon="chat-left-text">
                @if ($budget->description)
                    <x-layout.grid-col size="col-md-6">
                        <x-resource.resource-info
                            title="Descrição"
                            :subtitle="$budget->description"
                            class="small" />
                    </x-layout.grid-col>
                @endif
                @if ($budget->payment_terms)
                    <x-layout.grid-col size="col-md-6">
                        <x-resource.resource-info
                            title="Condições de Pagamento"
                            :subtitle="$budget->payment_terms"
                            class="small" />
                    </x-layout.grid-col>
                @endif
            </x-resource.resource-header-section>
        @endif

        {{-- Comentário do Cliente --}}
        @if ($budget->customer_comment)
            <x-resource.resource-header-divider />
            <x-resource.resource-header-section title="Comentário do Cliente" icon="chat-quote-fill">
                <x-layout.grid-col size="col-12">
                    <div class="p-3 rounded-3 border-start border-warning border-4" style="background-color: #fffcf0;">
                        <p class="mb-0 text-dark small fst-italic">"{{ $budget->customer_comment }}"</p>
                    </div>
                </x-layout.grid-col>
            </x-resource.resource-header-section>
        @endif
    </x-resource.resource-header-card>

        {{-- Serviços Vinculados --}}
        <x-resource.resource-list-card
            title="Serviços Vinculados"
            mobileTitle="Serviços"
            icon="tools"
            :total="$budget->services?->count() ?? 0">
            <x-slot:actions>
                @if ($budget->canBeEdited())
                <x-ui.button type="link" :href="route('provider.budgets.services.create', $budget->code)"
                    variant="success" size="sm" icon="plus" label="Novo Serviço" />
                @endif
            </x-slot:actions>

            @if ($budget->services && $budget->services->count())
            <x-slot:desktop>
                <x-resource.resource-table>
                    <x-slot:thead>
                        <x-resource.table-row>
                            <x-resource.table-cell header>Código</x-resource.table-cell>
                            <x-resource.table-cell header>Descrição</x-resource.table-cell>
                            <x-resource.table-cell header>Categoria</x-resource.table-cell>
                            <x-resource.table-cell header align="center">Status</x-resource.table-cell>
                            <x-resource.table-cell header align="end">Total</x-resource.table-cell>
                            <x-resource.table-cell header align="center">Ações</x-resource.table-cell>
                        </x-resource.table-row>
                    </x-slot:thead>
                    <x-slot:tbody>
                        @foreach ($budget->services as $service)
                        <x-resource.table-row>
                            <x-resource.table-cell class="fw-bold text-dark">{{ $service->code }}</x-resource.table-cell>
                            <x-resource.table-cell>
                                <x-resource.table-cell-truncate :text="$service->description" :limit="50" />
                            </x-resource.table-cell>
                            <x-resource.table-cell>{{ $service->category?->name ?? '-' }}</x-resource.table-cell>
                            <x-resource.table-cell align="center">
                                <x-ui.status-badge :item="$service" />
                            </x-resource.table-cell>
                            <x-resource.table-cell align="end" class="text-primary fw-bold">
                                R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}
                            </x-resource.table-cell>
                            <x-resource.table-cell align="center">
                                <x-resource.action-buttons
                                    :item="$service"
                                    resource="services"
                                    identifier="code"
                                    size="sm"
                                    :showDelete="false" />
                            </x-resource.table-cell>
                        </x-resource.table-row>
                        @endforeach
                    </x-slot:tbody>
                </x-resource.resource-table>
            </x-slot:desktop>

            <x-slot:mobile>
                @foreach ($budget->services as $service)
                <x-resource.resource-mobile-item
                    :href="route('provider.services.show', $service->code)">
                    <x-resource.resource-mobile-header
                        :title="$service->code"
                        :subtitle="$service->category?->name ?? 'Sem categoria'" />

                    <x-slot:description>
                        <p class="text-muted small mb-2">{{ Str::limit($service->description, 100) }}</p>
                        <div class="row g-2 w-100">
                            <x-resource.resource-mobile-field
                                col="col-6"
                                label="Total">
                                <span class="fw-bold text-primary">R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
                            </x-resource.resource-mobile-field>

                            <x-resource.resource-mobile-field
                                col="col-6"
                                align="end"
                                label="Status">
                                <x-ui.status-badge :item="$service" />
                            </x-resource.resource-mobile-field>
                        </div>
                    </x-slot:description>

                    <x-slot:actions>
                        <x-resource.action-buttons
                            :item="$service"
                            resource="services"
                            identifier="code"
                            size="sm"
                            :showDelete="false" />
                    </x-slot:actions>
                </x-resource.resource-mobile-item>
                @endforeach
            </x-slot:mobile>
            @else
            <div class="py-5">
                <x-resource.empty-state
                    resource="serviços"
                    icon="tools"
                    message="Nenhum serviço vinculado a este orçamento" />
            </div>
            @endif
        </x-resource.resource-list-card>

        {{-- Histórico de Ações --}}
        @if($budget->actionHistory && $budget->actionHistory->isNotEmpty())
        <x-resource.resource-list-card
            title="Histórico de Ações"
            mobileTitle="Histórico"
            icon="clock-history"
            :total="$budget->actionHistory->count()">

            <x-slot:desktop>
                <x-resource.resource-table>
                    <x-slot:thead>
                        <x-resource.table-row>
                            <x-resource.table-cell header>Data/Hora</x-resource.table-cell>
                            <x-resource.table-cell header>Ação</x-resource.table-cell>
                            <x-resource.table-cell header>Descrição/Comentário</x-resource.table-cell>
                            <x-resource.table-cell header>Usuário/Origem</x-resource.table-cell>
                        </x-resource.table-row>
                    </x-slot:thead>
                    <x-slot:tbody>
                        @foreach ($budget->actionHistory as $history)
                        <x-resource.table-row>
                            <x-resource.table-cell>
                                <x-resource.table-cell-datetime :datetime="$history->created_at" />
                            </x-resource.table-cell>
                            <x-resource.table-cell>
                                <span class="badge bg-light text-dark border">
                                    {{ $history->action_label }}
                                </span>
                            </x-resource.table-cell>
                            <x-resource.table-cell>
                                <span class="text-dark">{{ $history->description }}</span>
                                @if(isset($history->metadata['customer_comment']) && $history->metadata['customer_comment'])
                                <div class="mt-1 small text-muted fst-italic">
                                    <i class="bi bi-chat-quote me-1"></i>"{{ $history->metadata['customer_comment'] }}"
                                </div>
                                @endif
                                @if(isset($history->metadata['custom_message']) && $history->metadata['custom_message'])
                                <div class="mt-1 small text-primary fst-italic">
                                    <i class="bi bi-envelope-paper me-1"></i>"{{ $history->metadata['custom_message'] }}"
                                </div>
                                @endif
                            </x-resource.table-cell>
                            <x-resource.table-cell class="small text-muted">
                                @if(isset($history->metadata['via']) && $history->metadata['via'] === 'public_share')
                                <span class="badge bg-info text-white">Cliente (Link Público)</span>
                                @elseif($history->user)
                                {{ $history->user->name }}
                                @else
                                Sistema
                                @endif
                            </x-resource.table-cell>
                        </x-resource.table-row>
                        @endforeach
                    </x-slot:tbody>
                </x-resource.resource-table>
            </x-slot:desktop>

            <x-slot:mobile>
                @foreach ($budget->actionHistory as $history)
                <x-resource.resource-mobile-item>
                    <x-resource.resource-mobile-header
                        :title="$history->action_label"
                        :subtitle="$history->created_at->format('d/m/Y H:i')" />

                    <x-slot:description>
                        <p class="mb-2 small text-dark">{{ $history->description }}</p>
                        @if(isset($history->metadata['customer_comment']) && $history->metadata['customer_comment'])
                        <div class="mt-1 small text-muted fst-italic p-2 bg-light rounded border-start border-warning border-4">
                            <i class="bi bi-chat-quote me-1"></i>"{{ $history->metadata['customer_comment'] }}"
                        </div>
                        @endif
                        @if(isset($history->metadata['custom_message']) && $history->metadata['custom_message'])
                        <div class="mt-1 small text-primary fst-italic p-2 bg-light rounded border-start border-primary border-4">
                            <i class="bi bi-envelope-paper me-1"></i>"{{ $history->metadata['custom_message'] }}"
                        </div>
                        @endif
                    </x-slot:description>

                    <x-slot:footer>
                        <div class="text-end">
                            <span class="badge bg-secondary text-white small" style="font-size: 0.7rem;">
                                @if(isset($history->metadata['via']) && $history->metadata['via'] === 'public_share')
                                Cliente (Link Público)
                                @elseif($history->user)
                                {{ $history->user->name }}
                                @else
                                Sistema
                                @endif
                            </span>
                        </div>
                    </x-slot:footer>
                </x-resource.resource-mobile-item>
                @endforeach
            </x-slot:mobile>
        </x-resource.resource-list-card>
        @endif

        {{-- Botões de Ação --}}
        <div class="pt-2 pb-2">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-auto">
                    <x-ui.back-button index-route="provider.budgets.index" class="w-100 w-md-auto px-md-3" />
                </div>

                <div class="col-12 col-md">
                    {{-- Espaçador central --}}
                </div>

                <div class="col-12 col-md-auto">
                    <div class="d-grid d-md-flex gap-2">
                        <x-ui.button type="button" class="d-flex align-items-center"
                            variant="{{ ($isSent && !$isDraft) ? 'outline-info' : 'info' }}"
                            icon="send-fill"
                            :label="$sendModalLabel"
                            data-bs-toggle="modal" data-bs-target="#sendToCustomerModal" />

                        <x-ui.button type="link" :href="route('provider.budgets.shares.create', ['budget_id' => $budget->id])"
                            variant="outline-secondary" icon="share-fill" label="Links" />

                        @if ($budget->canBeEdited())
                        <x-ui.button type="link" :href="route('provider.budgets.edit', $budget->code)"
                            variant="primary" icon="pencil-fill" label="Editar" />
                        @endif

                        <x-ui.button type="link" :href="route('provider.budgets.print', ['code' => $budget->code, 'pdf' => true])"
                            target="_blank"
                            variant="outline-secondary"
                            icon="file-earmark-pdf"
                            label="Imprimir PDF" />
                    </div>
                </div>
            </div>
        </div>
    </x-layout.v-stack>
</x-layout.page-container>

<x-ui.modal id="sendToCustomerModal" :title="$sendModalTitle" icon="send-fill">
    <form action="{{ route('provider.budgets.send-to-customer', $budget->code) }}" method="POST">
        @csrf
        <div class="p-1">
            <p class="text-muted mb-3">O orçamento será enviado para o e-mail: <strong>{{ $budget->customer->contact->email_personal ?? 'E-mail não cadastrado' }}</strong></p>

            @if(!($budget->customer->contact->email_personal))
            <x-ui.alert type="warning" icon="exclamation-triangle">
                O cliente não possui e-mail pessoal cadastrado. Por favor, atualize o cadastro do cliente antes de enviar.
            </x-ui.alert>
            @endif

            <div class="mb-3">
                <x-form.input-label for="message" value="Mensagem Personalizada (Opcional)" class="fw-bold" />
                <textarea class="form-control" id="message" name="message" rows="4"
                    maxlength="255" placeholder="Olá, segue o orçamento solicitado..."
                    oninput="updateCharCount(this, 'charCountText')"></textarea>
                <div class="form-text text-end small" id="charCountText">0 / 255 caracteres</div>
            </div>

            <x-ui.alert type="info" icon="info-circle">
                @if($isSent)
                O orçamento já foi enviado. O reenvio atualizará o PDF e gerará um novo link de acesso.
                @else
                O PDF do orçamento será gerado e o link de visualização pública será criado.
                @endif
                <hr class="my-2 opacity-25">
                <i class="bi bi-shield-check me-1"></i>
                <strong>Reserva de Estoque:</strong> Conforme nossa política, os produtos serão reservados automaticamente apenas quando o serviço for movido para o status <strong>"Em Preparação"</strong>.
            </x-ui.alert>
        </div>

        <x-slot:footer>
            <x-ui.button type="button" variant="light" label="Cancelar" data-bs-dismiss="modal" />
            <x-ui.button type="submit" variant="info" class="text-white"
                :disabled="!($budget->customer->contact->email_personal)"
                icon="send"
                label="{{ ($isSent && !$isDraft) ? 'Reenviar Agora' : 'Enviar E-mail' }}" />
        </x-slot:footer>
    </form>
</x-ui.modal>
@endsection

@push('scripts')
<script>
    function updateCharCount(textarea, counterId) {
        const count = textarea.value.length;
        const counter = document.getElementById(counterId);
        if (counter) {
            counter.textContent = `${count} / 255 caracteres`;
            counter.classList.toggle('text-danger', count >= 255);
        }
    }
</script>
@endpush
