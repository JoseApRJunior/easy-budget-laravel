@extends('layouts.app')

@section('title', 'Gerador de QR Code')

@section('content')
<div class="container-fluid py-4">
    <x-layout.page-header
        title="Gerador de QR Code"
        icon="qr-code"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'QR Code' => '#'
        ]">
        <p class="text-muted mb-0">Gere QR Codes para compartilhamento e verificação de documentos</p>
    </x-layout.page-header>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0">
                <i class="bi bi-qr-code-scan me-2 text-primary"></i>
                <span class="d-none d-sm-inline">Gerador de QR Code</span>
                <span class="d-sm-none">Gerador</span>
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
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
                </div>

                <div class="col-md-6 border-start-md">
                    <div class="text-center p-3 h-100 d-flex flex-column align-items-center justify-content-center bg-light rounded shadow-inner">
                        <h6 class="text-uppercase text-muted small mb-3">Resultado</h6>
                        <div id="qrResult" class="qr-placeholder">
                            <div class="py-5">
                                <i class="bi bi-qr-code text-light-emphasis" style="font-size: 4rem;"></i>
                                <p class="text-muted mt-2">O QR Code aparecerá aqui...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        @php
            $actions = [
                ['id' => 'budget', 'color' => 'primary', 'icon' => 'file-invoice', 'title' => 'Orçamentos', 'target' => '#budgetModal'],
                ['id' => 'invoice', 'color' => 'success', 'icon' => 'currency-dollar', 'title' => 'Faturas', 'target' => '#invoiceModal'],
                ['id' => 'service', 'color' => 'info', 'icon' => 'tools', 'title' => 'Serviços', 'target' => '#serviceModal'],
            ];
        @endphp

        @foreach($actions as $action)
        <div class="col-md-4">
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
        </div>
        @endforeach
    </div>
</div>

<div class="modal fade" id="budgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-file-invoice me-2"></i>QR para Orçamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="budgetQrForm">
                @csrf
                <div class="modal-body">
                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" id="modal_budget_id" name="budget_id" placeholder="ID" required>
                        <label for="modal_budget_id">ID do Orçamento *</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="url" class="form-control" id="modal_budget_url" name="url" placeholder="URL" required>
                        <label for="modal_budget_url">URL de Verificação *</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">Gerar QR Code</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="invoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-currency-dollar me-2"></i>QR para Fatura</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="invoiceQrForm">
                @csrf
                <div class="modal-body">
                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" id="modal_invoice_id" name="invoice_id" placeholder="ID" required>
                        <label for="modal_invoice_id">ID da Fatura *</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="url" class="form-control" id="modal_invoice_url" name="url" placeholder="URL" required>
                        <label for="modal_invoice_url">URL de Verificação *</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success">Gerar QR Code</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-tools me-2"></i>QR para Serviço</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="serviceQrForm">
                @csrf
                <div class="modal-body">
                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" id="modal_service_id" name="service_id" placeholder="ID" required>
                        <label for="modal_service_id">ID do Serviço *</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="url" class="form-control" id="modal_service_url" name="url" placeholder="URL" required>
                        <label for="modal_service_url">URL de Verificação *</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-info">Gerar QR Code</button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

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
