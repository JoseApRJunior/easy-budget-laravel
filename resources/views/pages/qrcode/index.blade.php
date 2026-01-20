@extends('layouts.app')

@section('title', 'Gerador de QR Code')

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Gerador de QR Code"
        icon="qr-code"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'QR Code' => '#'
        ]"
        description="Gere QR Codes para compartilhamento e verificação de documentos"
    />

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0">
                <i class="bi bi-qr-code-scan me-2 text-primary"></i>
                <span class="d-none d-sm-inline">Gerador de QR Code</span>
                <span class="d-sm-none">Gerador</span>
            </h5>
        </div>
        <div class="card-body">
            <x-layout.grid-row class="g-4">
                <x-layout.grid-col size="col-md-6">
                    <form id="qrForm" class="qr-submit-form">
                        @csrf
                        <div class="form-floating mb-3">
                            <textarea class="form-control @error('text') is-invalid @enderror"
                                id="text" name="text" style="height: 120px"
                                placeholder="Digite o texto ou URL..." required>{{ old('text') }}</textarea>
                            <label for="text">Texto ou URL *</label>
                        </div>

                        <div class="mb-3">
                            <label for="size" class="form-label small fw-bold">Tamanho da Imagem:</label>
                            <select class="form-select" id="size" name="size">
                                <option value="180">180x180 (Pequeno)</option>
                                <option value="256" selected>256x256 (Padrão)</option>
                                <option value="512">512x512 (Alta Resolução)</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <x-ui.button type="submit" variant="primary" icon="qr-code" label="Gerar QR Code" class="flex-grow-1" />
                            <x-ui.button type="button" variant="outline-secondary" id="clearBtn" icon="eraser" label="Limpar" />
                        </div>
                    </form>
                </x-layout.grid-col>

                <x-layout.grid-col size="col-md-6" class="border-start-md">
                    <div class="text-center p-3 h-100 d-flex flex-column align-items-center justify-content-center bg-light rounded shadow-inner">
                        <h6 class="text-uppercase text-dark fw-bold small mb-3" style="letter-spacing: 1px;">Resultado</h6>
                        <div id="qrResult" class="qr-placeholder">
                            <div class="py-5">
                                <i class="bi bi-qr-code text-primary opacity-25" style="font-size: 4rem;"></i>
                                <p class="text-dark opacity-75 mt-2">O QR Code aparecerá aqui...</p>
                            </div>
                        </div>
                    </div>
                </x-layout.grid-col>
            </x-layout.grid-row>
        </div>
    </div>

    <x-layout.grid-row>
        @php
            $actions = [
                ['id' => 'budget', 'color' => 'primary', 'icon' => 'file-invoice', 'title' => 'Orçamentos', 'target' => '#budgetModal'],
                ['id' => 'invoice', 'color' => 'success', 'icon' => 'currency-dollar', 'title' => 'Faturas', 'target' => '#invoiceModal'],
                ['id' => 'service', 'color' => 'info', 'icon' => 'tools', 'title' => 'Serviços', 'target' => '#serviceModal'],
            ];
        @endphp

        @foreach($actions as $action)
        <x-layout.grid-col size="col-md-4">
            <div class="card border-0 shadow-sm h-100 text-center hover-shadow transition">
                <div class="card-body">
                    <div class="avatar-circle bg-{{ $action['color'] }} bg-gradient mb-3 mx-auto shadow-sm">
                        <i class="bi bi-{{ $action['icon'] }} text-white"></i>
                    </div>
                    <h5 class="card-title">{{ $action['title'] }}</h5>
                    <p class="card-text text-muted small">Gerar QR Code para verificação rápida de {{ strtolower($action['title']) }}</p>
                    <x-ui.button variant="{{ $action['color'] }}" size="sm" icon="qr-code" label="Gerar QR {{ Str::singular($action['title']) }}"
                        data-bs-toggle="modal" data-bs-target="{{ $action['target'] }}" />
                </div>
            </div>
        </x-layout.grid-col>
        @endforeach
    </x-layout.grid-row>
</x-layout.page-container>

<x-ui.modal id="budgetModal" title="QR para Orçamento">
    <form id="budgetQrForm">
        @csrf
        <div class="form-floating mb-3">
            <input type="number" class="form-control" id="modal_budget_id" name="budget_id" placeholder="ID" required>
            <label for="modal_budget_id">ID do Orçamento *</label>
        </div>
        <div class="form-floating mb-3">
            <input type="url" class="form-control" id="modal_budget_url" name="url" placeholder="URL" required>
            <label for="modal_budget_url">URL de Verificação *</label>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-sm btn-primary">Gerar QR Code</button>
        </div>
    </form>
</x-ui.modal>

<x-ui.modal id="invoiceModal" title="QR para Fatura">
    <form id="invoiceQrForm">
        @csrf
        <div class="form-floating mb-3">
            <input type="number" class="form-control" id="modal_invoice_id" name="invoice_id" placeholder="ID" required>
            <label for="modal_invoice_id">ID da Fatura *</label>
        </div>
        <div class="form-floating mb-3">
            <input type="url" class="form-control" id="modal_invoice_url" name="url" placeholder="URL" required>
            <label for="modal_invoice_url">URL de Verificação *</label>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-sm btn-success">Gerar QR Code</button>
        </div>
    </form>
</x-ui.modal>

<x-ui.modal id="serviceModal" title="QR para Serviço">
    <form id="serviceQrForm">
        @csrf
        <div class="form-floating mb-3">
            <input type="number" class="form-control" id="modal_service_id" name="service_id" placeholder="ID" required>
            <label for="modal_service_id">ID do Serviço *</label>
        </div>
        <div class="form-floating mb-3">
            <input type="url" class="form-control" id="modal_service_url" name="url" placeholder="URL" required>
            <label for="modal_service_url">URL de Verificação *</label>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-sm btn-info">Gerar QR Code</button>
        </div>
    </form>
</x-ui.modal>
@stop

@push('styles')
<style>
    .border-start-md {
        border-left: 1px solid #dee2e6;
    }

    @media (max-width: 767.98px) {
        .border-start-md {
            border-left: none;
            border-top: 1px solid #dee2e6;
            padding-top: 1.5rem;
            margin-top: 1rem;
        }
    }

    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }

    .transition {
        transition: all 0.3s ease;
    }

    .avatar-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
</style>
@endpush

@section('scripts')
<script>
    // Aguarda o evento de carregamento do DOM para garantir que o script 'defer' do jQuery já tenha executado
    window.addEventListener('DOMContentLoaded', function() {

        // Encapsulamento de segurança: garante que o '$' seja reconhecido como o jQuery localmente
        (function($) {

            const $qrResult = $('#qrResult');

            // Configuração AJAX Centralizada
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            /**
             * Função Mestra para processar AJAX
             */
            function handleQrSubmission(formId, routeUrl, successMessage) {
                $(formId).on('submit', function(e) {
                    e.preventDefault();
                    const $form = $(this);
                    const $btn = $form.find('button[type="submit"]');
                    const originalHtml = $btn.html();

                    $btn.html('<span class="spinner-border spinner-border-sm"></span> Gerando...').prop('disabled', true);

                    $.ajax({
                        url: routeUrl,
                        type: 'POST',
                        data: $form.serialize(),
                        success: function(response) {
                            if (response.success) {
                                // Fecha modais se houver um aberto
                                $('.modal').modal('hide');

                                // Renderiza o resultado com animação
                                $qrResult.hide().html(`
                                    <div class="alert alert-success border-0 shadow-sm mb-3 text-start">
                                        <i class="bi bi-check-circle-fill me-2"></i> ${response.message || successMessage}
                                    </div>
                                    <div class="bg-white p-3 rounded shadow-sm d-inline-block mb-3">
                                        <img src="${response.data.qr_code}" alt="QR Code" class="img-fluid animate__animated animate__zoomIn" style="max-width: 280px;">
                                    </div>
                                    <div class="text-break small text-muted bg-white p-2 rounded">
                                        <strong>Conteúdo:</strong> ${response.data.text || response.data.url || 'Verificado'}
                                    </div>
                                    <div class="mt-3">
                                        <button onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="bi bi-printer"></i> Imprimir</button>
                                    </div>
                                `).fadeIn();

                                // Scroll suave até o resultado em telas pequenas
                                if($(window).width() < 768) {
                                    $('html, body').animate({ scrollTop: $qrResult.offset().top - 100 }, 500);
                                }

                                if (formId !== '#qrForm') $form[0].reset();
                            }
                        },
                        error: function(xhr) {
                            const msg = xhr.responseJSON?.message || 'Erro inesperado ao gerar QR Code.';
                            // Verifica se o SweetAlert2 (Swal) está disponível, senão usa alert comum
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'error', title: 'Erro', text: msg });
                            } else {
                                alert(msg);
                            }
                        },
                        complete: function() {
                            $btn.html(originalHtml).prop('disabled', false);
                        }
                    });
                });
            }

            // Inicialização dos formulários
            handleQrSubmission('#qrForm', '{{ route("provider.qrcode.generate") }}', 'QR Code gerado com sucesso!');
            handleQrSubmission('#budgetQrForm', '{{ route("provider.qrcode.budget") }}', 'QR de Orçamento pronto!');
            handleQrSubmission('#invoiceQrForm', '{{ route("provider.qrcode.invoice") }}', 'QR de Fatura pronto!');
            handleQrSubmission('#serviceQrForm', '{{ route("provider.qrcode.service") }}', 'QR de Serviço pronto!');

            // Botão Limpar
            $('#clearBtn').on('click', function() {
                $('#qrForm')[0].reset();
                $qrResult.html(`
                    <div class="py-5 text-muted">
                        <i class="bi bi-qr-code" style="font-size: 4rem;"></i>
                        <p>O QR Code aparecerá aqui...</p>
                    </div>
                `);
            });

        })(window.jQuery); // Passa o jQuery global para dentro da função
    });
</script>
@stop
