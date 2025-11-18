@extends('layouts.app')

@section('title', 'Gerador de QR Code')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gerador de QR Code</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form id="qrForm">
                                @csrf
                                <div class="form-group">
                                    <label for="text">Texto ou URL:</label>
                                    <textarea class="form-control" id="text" name="text" rows="4" placeholder="Digite o texto ou URL que deseja codificar..." required></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="size">Tamanho:</label>
                                    <select class="form-control" id="size" name="size">
                                        <option value="180">180x180</option>
                                        <option value="256" selected>256x256</option>
                                        <option value="300">300x300</option>
                                        <option value="400">400x400</option>
                                        <option value="512">512x512</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-qrcode"></i> Gerar QR Code
                                </button>
                                <button type="button" class="btn btn-secondary" id="clearBtn">
                                    <i class="fas fa-eraser"></i> Limpar
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center">
                                <h5>QR Code Gerado:</h5>
                                <div id="qrResult" class="mt-3">
                                    <p class="text-muted">O QR Code aparecerá aqui...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Ações Rápidas</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Orçamentos</h5>
                                    <p class="card-text">Gerar QR Code para verificação de orçamento</p>
                                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#budgetModal">
                                        <i class="fas fa-file-invoice"></i> Gerar QR Orçamento
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Faturas</h5>
                                    <p class="card-text">Gerar QR Code para verificação de fatura</p>
                                    <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#invoiceModal">
                                        <i class="fas fa-file-invoice-dollar"></i> Gerar QR Fatura
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Serviços</h5>
                                    <p class="card-text">Gerar QR Code para verificação de serviço</p>
                                    <button class="btn btn-info btn-sm" onclick="alert('Funcionalidade em desenvolvimento')">
                                        <i class="fas fa-tools"></i> Gerar QR Serviço
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Budget Modal -->
<div class="modal fade" id="budgetModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gerar QR Code para Orçamento</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="budgetQrForm">
                <div class="modal-body">
                    @csrf
                    <div class="form-group">
                        <label for="budget_id">ID do Orçamento:</label>
                        <input type="number" class="form-control" id="budget_id" name="budget_id" required>
                    </div>
                    <div class="form-group">
                        <label for="budget_url">URL de Verificação:</label>
                        <input type="url" class="form-control" id="budget_url" name="url" placeholder="https://..." required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Gerar QR Code</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Invoice Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gerar QR Code para Fatura</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="invoiceQrForm">
                <div class="modal-body">
                    @csrf
                    <div class="form-group">
                        <label for="invoice_id">ID da Fatura:</label>
                        <input type="number" class="form-control" id="invoice_id" name="invoice_id" required>
                    </div>
                    <div class="form-group">
                        <label for="invoice_url">URL de Verificação:</label>
                        <input type="url" class="form-control" id="invoice_url" name="url" placeholder="https://..." required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Gerar QR Code</button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Setup CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val()
        }
    });
    
    // Main QR Code generator
    $('#qrForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Gerando...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("provider.qrcode.generate") }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#qrResult').html(`
                        <div class="alert alert-success">
                            <strong>Sucesso!</strong> ${response.message}
                        </div>
                        <img src="${response.data.qr_code}" alt="QR Code" class="img-fluid" style="max-width: 300px;">
                        <div class="mt-2">
                            <small class="text-muted">Texto: ${response.data.text}</small>
                        </div>
                    `);
                } else {
                    $('#qrResult').html(`
                        <div class="alert alert-danger">
                            <strong>Erro!</strong> ${response.message}
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                $('#qrResult').html(`
                    <div class="alert alert-danger">
                        <strong>Erro!</strong> ${response?.message || 'Erro ao gerar QR Code'}
                    </div>
                `);
            },
            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Budget QR Code generator
    $('#budgetQrForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Gerando...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("provider.qrcode.budget") }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#budgetModal').modal('hide');
                    $('#qrResult').html(`
                        <div class="alert alert-success">
                            <strong>Sucesso!</strong> ${response.message}
                        </div>
                        <img src="${response.data.qr_code}" alt="QR Code" class="img-fluid" style="max-width: 300px;">
                        <div class="mt-2">
                            <small class="text-muted">Orçamento ID: ${response.data.budget_id}</small>
                        </div>
                    `);
                    // Clear form
                    $('#budgetQrForm')[0].reset();
                } else {
                    alert('Erro: ' + response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                alert('Erro: ' + (response?.message || 'Erro ao gerar QR Code'));
            },
            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Invoice QR Code generator
    $('#invoiceQrForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Gerando...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("provider.qrcode.invoice") }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#invoiceModal').modal('hide');
                    $('#qrResult').html(`
                        <div class="alert alert-success">
                            <strong>Sucesso!</strong> ${response.message}
                        </div>
                        <img src="${response.data.qr_code}" alt="QR Code" class="img-fluid" style="max-width: 300px;">
                        <div class="mt-2">
                            <small class="text-muted">Fatura ID: ${response.data.invoice_id}</small>
                        </div>
                    `);
                    // Clear form
                    $('#invoiceQrForm')[0].reset();
                } else {
                    alert('Erro: ' + response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                alert('Erro: ' + (response?.message || 'Erro ao gerar QR Code'));
            },
            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Clear button
    $('#clearBtn').on('click', function() {
        $('#qrForm')[0].reset();
        $('#qrResult').html('<p class="text-muted">O QR Code aparecerá aqui...</p>');
    });
});
</script>
@stop