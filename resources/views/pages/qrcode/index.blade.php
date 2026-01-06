@extends('layouts.app')

@section('title', 'Gerador de QR Code')

@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            title="Gerador de QR Code"
            icon="qr-code"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'QR Code' => '#'
            ]">
            <p class="text-muted mb-0">Gere QR Codes para compartilhamento e verificação de documentos</p>
        </x-page-header>

    <!-- Card principal do gerador -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0">
            <h5 class="mb-0">
                <i class="bi bi-qr-code-scan me-2"></i>
                <span class="d-none d-sm-inline">Gerador de QR Code</span>
                <span class="d-sm-none">Gerador</span>
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <form id="qrForm">
                        @csrf
                        <div class="form-floating mb-3">
                            <textarea class="form-control @error('text') is-invalid @enderror" id="text" name="text" rows="4"
                                placeholder="Digite o texto ou URL que deseja codificar..." required>{{ old('text') }}</textarea>
                            <label for="text">Texto ou URL *</label>
                            @error('text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-3">
                            <label for="size" class="form-label">Tamanho:</label>
                            <select class="form-select" id="size" name="size">
                                <option value="180">180x180</option>
                                <option value="256" selected>256x256</option>
                                <option value="300">300x300</option>
                                <option value="400">400x400</option>
                                <option value="512">512x512</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <x-button type="submit" variant="primary" icon="qr-code" label="Gerar QR Code" />
                            <x-button type="button" variant="outline-secondary" id="clearBtn" icon="eraser" label="Limpar" />
                        </div>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="text-center">
                        <h5 class="mb-3">QR Code Gerado:</h5>
                        <div id="qrResult" class="mt-3">
                            <p class="text-muted">O QR Code aparecerá aqui...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-rocket me-2"></i>
                        <span class="d-none d-sm-inline">Ações Rápidas</span>
                        <span class="d-sm-none">Ações</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card border-primary h-100">
                                <div class="card-body text-center">
                                    <div class="avatar-circle bg-primary bg-gradient mb-3 mx-auto">
                                        <i class="bi bi-file-invoice text-white"></i>
                                    </div>
                                    <h5 class="card-title mb-2">Orçamentos</h5>
                                    <p class="card-text text-muted small mb-3">Gerar QR Code para verificação de orçamento
                                    </p>
                                    <x-button variant="primary" size="sm" icon="qr-code" label="Gerar QR Orçamento" data-bs-toggle="modal" data-bs-target="#budgetModal" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-success h-100">
                                <div class="card-body text-center">
                                    <div class="avatar-circle bg-success bg-gradient mb-3 mx-auto">
                                        <i class="bi bi-currency-dollar text-white"></i>
                                    </div>
                                    <h5 class="card-title mb-2">Faturas</h5>
                                    <p class="card-text text-muted small mb-3">Gerar QR Code para verificação de fatura</p>
                                    <x-button variant="success" size="sm" icon="qr-code" label="Gerar QR Fatura" data-bs-toggle="modal" data-bs-target="#invoiceModal" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-info h-100">
                                <div class="card-body text-center">
                                    <div class="avatar-circle bg-info bg-gradient mb-3 mx-auto">
                                        <i class="bi bi-tools text-white"></i>
                                    </div>
                                    <h5 class="card-title mb-2">Serviços</h5>
                                    <p class="card-text text-muted small mb-3">Gerar QR Code para verificação de serviço</p>
                                    <x-button variant="info" size="sm" icon="qr-code" label="Gerar QR Serviço" data-bs-toggle="modal" data-bs-target="#serviceModal" />
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
                    <h5 class="modal-title">
                        <i class="bi bi-file-invoice me-2"></i>Gerar QR Code para Orçamento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="budgetQrForm">
                    <div class="modal-body">
                        @csrf
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control @error('budget_id') is-invalid @enderror"
                                id="budget_id" name="budget_id" required>
                            <label for="budget_id">ID do Orçamento *</label>
                            @error('budget_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-floating mb-3">
                            <input type="url" class="form-control @error('budget_url') is-invalid @enderror"
                                id="budget_url" name="url" placeholder="https://..." required>
                            <label for="budget_url">URL de Verificação *</label>
                            @error('budget_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <x-button variant="secondary" outline label="Cancelar" icon="x-circle" data-bs-dismiss="modal" />
                        <x-button type="submit" variant="primary" label="Gerar QR Code" icon="qr-code" />
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
                    <h5 class="modal-title">
                        <i class="bi bi-currency-dollar me-2"></i>Gerar QR Code para Fatura
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="invoiceQrForm">
                    <div class="modal-body">
                        @csrf
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control @error('invoice_id') is-invalid @enderror"
                                id="invoice_id" name="invoice_id" required>
                            <label for="invoice_id">ID da Fatura *</label>
                            @error('invoice_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-floating mb-3">
                            <input type="url" class="form-control @error('invoice_url') is-invalid @enderror"
                                id="invoice_url" name="url" placeholder="https://..." required>
                            <label for="invoice_url">URL de Verificação *</label>
                            @error('invoice_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <x-button variant="secondary" outline label="Cancelar" icon="x-circle" data-bs-dismiss="modal" />
                        <x-button type="submit" variant="success" label="Gerar QR Code" icon="qr-code" />
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Service Modal -->
    <div class="modal fade" id="serviceModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-tools me-2"></i>Gerar QR Code para Serviço
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="serviceQrForm">
                    <div class="modal-body">
                        @csrf
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control @error('service_id') is-invalid @enderror"
                                id="service_id" name="service_id" required>
                            <label for="service_id">ID do Serviço *</label>
                            @error('service_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-floating mb-3">
                            <input type="url" class="form-control @error('service_url') is-invalid @enderror"
                                id="service_url" name="url" placeholder="https://..." required>
                            <label for="service_url">URL de Verificação *</label>
                            @error('service_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <x-button variant="secondary" outline label="Cancelar" icon="x-circle" data-bs-dismiss="modal" />
                        <x-button type="submit" variant="info" label="Gerar QR Code" icon="qr-code" />
                    </div>
                </form>
            </div>
        </div>
    </div>

@stop

@section('scripts')
    <script>
        $(document).ready(function() {
            // Setup CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || $(
                        'input[name="_token"]').val()
                }
            });

            // Main QR Code generator
            $('#qrForm').on('submit', function(e) {
                e.preventDefault();

                const formData = $(this).serialize();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();

                submitBtn.html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Gerando...'
                    ).prop('disabled', true);

                $.ajax({
                    url: '{{ route('provider.qrcode.generate') }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#qrResult').html(`
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
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
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Erro!</strong> ${response.message}
                                </div>
                            `);
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        let errorMessage = 'Erro ao gerar QR Code';

                        if (xhr.status === 401) {
                            errorMessage =
                                'Você precisa estar logado para gerar QR codes. Por favor, faça login.';
                        } else if (xhr.status === 419) {
                            errorMessage =
                                'Sessão expirada. Por favor, recarregue a página e tente novamente.';
                        } else if (xhr.status === 302) {
                            errorMessage =
                                'Redirecionando para login... Você precisa estar autenticado.';
                        } else if (response?.message) {
                            errorMessage = response.message;
                        }

                        $('#qrResult').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Erro!</strong> ${errorMessage}
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

                submitBtn.html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Gerando...'
                    ).prop('disabled', true);

                $.ajax({
                    url: '{{ route('provider.qrcode.budget') }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#budgetModal').modal('hide');
                            $('#qrResult').html(`
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
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
                        let errorMessage = 'Erro ao gerar QR Code';

                        if (xhr.status === 401) {
                            errorMessage =
                                'Você precisa estar logado para gerar QR codes. Por favor, faça login.';
                        } else if (xhr.status === 419) {
                            errorMessage =
                                'Sessão expirada. Por favor, recarregue a página e tente novamente.';
                        } else if (xhr.status === 302) {
                            errorMessage =
                                'Redirecionando para login... Você precisa estar autenticado.';
                        } else if (response?.message) {
                            errorMessage = response.message;
                        }

                        alert('Erro: ' + errorMessage);
                    },
                    complete: function() {
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                });
            });

            // Service QR Code generator
            $('#serviceQrForm').on('submit', function(e) {
                e.preventDefault();

                const formData = $(this).serialize();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();

                submitBtn.html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Gerando...'
                    ).prop('disabled', true);

                $.ajax({
                    url: '{{ route('provider.qrcode.service') }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#serviceModal').modal('hide');
                            $('#qrResult').html(`
                                <div class="alert alert-info">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>Sucesso!</strong> ${response.message}
                                </div>
                                <img src="${response.data.qr_code}" alt="QR Code" class="img-fluid" style="max-width: 300px;">
                                <div class="mt-2">
                                    <small class="text-muted">Serviço ID: ${response.data.service_id}</small>
                                </div>
                            `);
                            // Clear form
                            $('#serviceQrForm')[0].reset();
                        } else {
                            alert('Erro: ' + response.message);
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        let errorMessage = 'Erro ao gerar QR Code';

                        if (xhr.status === 401) {
                            errorMessage =
                                'Você precisa estar logado para gerar QR codes. Por favor, faça login.';
                        } else if (xhr.status === 419) {
                            errorMessage =
                                'Sessão expirada. Por favor, recarregue a página e tente novamente.';
                        } else if (xhr.status === 302) {
                            errorMessage =
                                'Redirecionando para login... Você precisa estar autenticado.';
                        } else if (response?.message) {
                            errorMessage = response.message;
                        }

                        alert('Erro: ' + errorMessage);
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

                submitBtn.html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Gerando...'
                    ).prop('disabled', true);

                $.ajax({
                    url: '{{ route('provider.qrcode.invoice') }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#invoiceModal').modal('hide');
                            $('#qrResult').html(`
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
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
                        let errorMessage = 'Erro ao gerar QR Code';

                        if (xhr.status === 401) {
                            errorMessage =
                                'Você precisa estar logado para gerar QR codes. Por favor, faça login.';
                        } else if (xhr.status === 419) {
                            errorMessage =
                                'Sessão expirada. Por favor, recarregue a página e tente novamente.';
                        } else if (xhr.status === 302) {
                            errorMessage =
                                'Redirecionando para login... Você precisa estar autenticado.';
                        } else if (response?.message) {
                            errorMessage = response.message;
                        }

                        alert('Erro: ' + errorMessage);
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
    </div>
@stop
