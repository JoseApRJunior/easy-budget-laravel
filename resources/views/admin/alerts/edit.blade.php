@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="bi bi-pencil me-2"></i>Editar Alerta
                </h1>
                <div class="btn-group" role="group">
                    <x-ui.button type="link" :href="route('admin.alerts.show', $alert['id'])" variant="info" icon="eye" label="Ver" />
                    <x-ui.button type="link" :href="route('admin.alerts.index')" variant="secondary" icon="arrow-left" label="Voltar" />
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>Editar Alerta #{{ $alert['id'] }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.alerts.update', $alert['id']) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">
                                    <i class="bi bi-tag me-1"></i>Tipo de Alerta
                                </label>
                                <select class="form-select @error('type') is-invalid @enderror" 
                                        id="type" 
                                        name="type" 
                                        required>
                                    <option value="">Selecione o tipo...</option>
                                    @foreach($alertTypes as $value => $label)
                                        <option value="{{ $value }}" 
                                                {{ old('type', $alert['type']) == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="severity" class="form-label">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Severidade
                                </label>
                                <select class="form-select @error('severity') is-invalid @enderror" 
                                        id="severity" 
                                        name="severity" 
                                        required>
                                    <option value="">Selecione a severidade...</option>
                                    @foreach($severities as $value => $label)
                                        <option value="{{ $value }}" 
                                                {{ old('severity', $alert['severity']) == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('severity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">
                                <i class="bi bi-card-text me-1"></i>Título do Alerta
                            </label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title', $alert['title']) }}" 
                                   placeholder="Digite um título descritivo para o alerta..."
                                   required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">
                                <i class="bi bi-chat-text me-1"></i>Mensagem do Alerta
                            </label>
                            <textarea class="form-control @error('message') is-invalid @enderror" 
                                      id="message" 
                                      name="message" 
                                      rows="4" 
                                      placeholder="Descreva detalhadamente o que este alerta está reportando..."
                                      required>{{ old('message', $alert['message']) }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">
                                <i class="bi bi-toggle-on me-1"></i>Status
                            </label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" 
                                    name="status" 
                                    required>
                                <option value="active" {{ old('status', $alert['status']) == 'active' ? 'selected' : '' }}>
                                    Ativo
                                </option>
                                <option value="resolved" {{ old('status', $alert['status']) == 'resolved' ? 'selected' : '' }}>
                                    Resolvido
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <x-ui.button type="link" :href="route('admin.alerts.show', $alert['id'])" variant="secondary" icon="x-circle" label="Cancelar" />
                            <x-ui.button type="submit" variant="primary" icon="save" label="Atualizar Alerta" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Adicionar validação dinâmica
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const severitySelect = document.getElementById('severity');
        const typeSelect = document.getElementById('type');
        
        // Mudar cor do select baseado na severidade
        severitySelect.addEventListener('change', function() {
            this.className = this.className.replace(/bg-\w+/, '');
            if (this.value === 'danger') {
                this.classList.add('bg-danger', 'text-white');
            } else if (this.value === 'warning') {
                this.classList.add('bg-warning', 'text-dark');
            } else if (this.value === 'info') {
                this.classList.add('bg-info', 'text-white');
            }
        });
        
        // Trigger inicial
        severitySelect.dispatchEvent(new Event('change'));
        
        // Adicionar confirmação antes de sair da página se houver mudanças
        let formChanged = false;
        form.addEventListener('change', function() {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        form.addEventListener('submit', function() {
            formChanged = false;
        });
    });
</script>
@endpush