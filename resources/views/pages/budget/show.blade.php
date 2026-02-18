@extends('layouts.app')

@section('title', 'Detalhes do Orçamento')

@php
    $isSent = $budget->actionHistory()->whereIn('action', ['sent_and_reserved', 'sent'])->exists();
    $isDraft = $budget->status->value === 'draft';
    $sendModalTitle = ($isSent && !$isDraft) ? 'Reenviar Orçamento' : 'Enviar Orçamento';
    $sendModalLabel = ($isSent && !$isDraft) ? 'Reenviar' : 'Enviar';

    // Buscar e-mail de várias fontes
    $customerEmail = $budget->customer->email
        ?? $budget->customer->contact->email_personal
        ?? $budget->customer->contact->email_business
        ?? null;
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
                <x-layout.h-stack gap="2">
                    @if ($budget->canBeEdited())
                    <x-ui.button type="link" :href="route('provider.budgets.edit', $budget->code)"
                        variant="light" size="sm" icon="pencil" label="Editar" feature="budgets" />
                    @endif
                    <x-ui.button type="link" :href="route('provider.budgets.print', ['code' => $budget->code])"
                        variant="light" size="sm" icon="printer" label="Imprimir" target="_blank" feature="budgets" />
                </x-layout.h-stack>
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
                    <x-ui.comment-box
                        variant="warning"
                        icon="quote"
                        label="Mensagem do Cliente"
                        :message="$budget->customer_comment"
                        class="p-3" />
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
                    variant="success" size="sm" icon="plus" label="Novo Serviço" feature="budgets" />
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
                <x-resource.resource-mobile-item class="service-item">
                    <x-resource.resource-mobile-header
                        :title="$service->code"
                        :subtitle="$service->category?->name ?? 'Sem categoria'" />

                    <x-slot:description>
                        <p class="text-muted small mb-2 text-truncate-2">{{ $service->description }}</p>
                        <x-layout.grid-row class="g-2 mb-0">
                            <x-resource.resource-mobile-field
                                col="col-5"
                                label="Total">
                                <span class="fw-bold text-primary">R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
                            </x-resource.resource-mobile-field>

                            <x-resource.resource-mobile-field
                                col="col-7"
                                align="end"
                                label="Status">
                                <x-ui.status-badge :item="$service" />
                            </x-resource.resource-mobile-field>
                        </x-layout.grid-row>
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
            <x-resource.empty-state
                resource="serviços"
                icon="tools"
                message="Nenhum serviço vinculado a este orçamento" />
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
                                <x-ui.badge :label="$history->action_label" variant="light" />
                            </x-resource.table-cell>
                            <x-resource.table-cell>
                                <x-ui.text weight="medium">{{ $history->description }}</x-ui.text>
                                @if(isset($history->metadata['customer_comment']) && $history->metadata['customer_comment'])
                                    <x-ui.comment-box variant="warning" icon="quote" :message="$history->metadata['customer_comment']" class="mt-1" />
                                @endif
                                @if(isset($history->metadata['custom_message']) && $history->metadata['custom_message'])
                                    <x-ui.comment-box variant="primary" icon="envelope-paper" :message="$history->metadata['custom_message']" class="mt-1" />
                                @endif
                            </x-resource.table-cell>
                            <x-resource.table-cell class="small text-muted">
                                @if(isset($history->metadata['via']) && $history->metadata['via'] === 'public_share')
                                <x-ui.badge label="Cliente (Link Público)" variant="info" />
                                @elseif($history->user)
                                {{ $history->user->name }}
                                @else
                                <span class="text-muted italic">Sistema</span>
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
                        <x-layout.v-stack gap="2">
                            <x-ui.text weight="medium" size="sm">{{ $history->description }}</x-ui.text>
                            @if(isset($history->metadata['customer_comment']) && $history->metadata['customer_comment'])
                                <x-ui.comment-box variant="warning" icon="quote" :message="$history->metadata['customer_comment']" />
                            @endif
                            @if(isset($history->metadata['custom_message']) && $history->metadata['custom_message'])
                                <x-ui.comment-box variant="primary" icon="envelope-paper" :message="$history->metadata['custom_message']" />
                            @endif
                        </x-layout.v-stack>
                    </x-slot:description>

                    <x-slot:footer>
                        <x-layout.h-stack justify="end">
                            @if(isset($history->metadata['via']) && $history->metadata['via'] === 'public_share')
                                <x-ui.badge label="Cliente (Link Público)" variant="info" pill />
                            @elseif($history->user)
                                <x-ui.badge :label="$history->user->name" variant="secondary" pill />
                            @else
                                <x-ui.badge label="Sistema" variant="light" pill />
                            @endif
                        </x-layout.h-stack>
                    </x-slot:footer>
                </x-resource.resource-mobile-item>
                @endforeach
            </x-slot:mobile>
        </x-resource.resource-list-card>
        @endif

        {{-- Botões de Ação --}}
        <x-layout.actions-bar alignment="between" class="mt-4" mb="0">
            <x-ui.back-button index-route="provider.budgets.index" class="w-100 w-md-auto px-md-4" />

            <x-ui.button-group gap="2" class="w-100 w-md-auto">
                <x-ui.button type="button"
                    variant="{{ ($isSent && !$isDraft) ? 'outline-info' : 'info' }}"
                    icon="send-fill"
                    :label="$sendModalLabel"
                    data-bs-toggle="modal" data-bs-target="#sendToCustomerModal" feature="budgets" />

                <x-ui.button type="link" :href="route('provider.budgets.shares.create', ['budget_id' => $budget->id])"
                    variant="outline-secondary" icon="share-fill" label="Links" feature="budgets" />

                @if ($budget->canBeEdited())
                <x-ui.button type="link" :href="route('provider.budgets.edit', $budget->code)"
                    variant="primary" icon="pencil-fill" label="Editar" feature="budgets" />
                @endif

                <x-ui.button type="link" :href="route('provider.budgets.print', ['code' => $budget->code])"
                    target="_blank"
                    variant="outline-secondary"
                    icon="printer"
                    label="Imprimir" feature="budgets" />
            </x-ui.button-group>
        </x-layout.actions-bar>
    </x-layout.v-stack>
</x-layout.page-container>

<x-ui.modal id="sendToCustomerModal" :title="$sendModalTitle" icon="send-fill">
    <form id="sendToCustomerForm" action="{{ route('provider.budgets.send-to-customer', $budget->code) }}" method="POST">
        @csrf
        <x-layout.v-stack gap="3">
            <x-ui.text variant="small">
                O orçamento será enviado para o e-mail: <strong>{{ $customerEmail ?? 'E-mail não cadastrado' }}</strong>
            </x-ui.text>

            @if(!$customerEmail)
            <x-ui.alert type="warning" icon="exclamation-triangle">
                O cliente não possui e-mail cadastrado. Por favor, atualize o cadastro do cliente antes de enviar.
            </x-ui.alert>
            @endif

            <x-ui.form.textarea
                name="message"
                label="Mensagem Personalizada (Opcional)"
                rows="4"
                maxlength="255"
                placeholder="Olá, segue o orçamento solicitado..."
                oninput="updateCharCount(this, 'charCountText')">
                <x-slot:helpSlot>
                    <x-ui.form.char-count id="charCountText" max="255" />
                </x-slot:helpSlot>
            </x-ui.form.textarea>

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
        </x-layout.v-stack>
    </form>

    <x-slot:footer>
        <x-layout.h-stack justify="end" gap="2">
            <x-ui.button type="button" variant="light" label="Cancelar" data-bs-dismiss="modal" />
            <x-ui.button type="submit" form="sendToCustomerForm" variant="primary" icon="send-fill" label="Confirmar e Enviar" feature="budgets" :disabled="!$customerEmail" />
        </x-layout.h-stack>
    </x-slot:footer>
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
