<x-app-layout title="Editar Alerta">
    <x-layout.page-container>
        <x-layout.page-header
            title="Editar Alerta"
            icon="pencil"
            :breadcrumb-items="[
                'Admin' => route('admin.dashboard'),
                'Alertas' => route('admin.alerts.index'),
                'Editar' => '#'
            ]">
            <x-slot:actions>
                <div class="d-flex gap-2">
                    <x-ui.button type="link" :href="route('admin.alerts.show', $alert['id'])" variant="info" icon="eye" label="Ver" feature="manage-alerts" />
                    <x-ui.button type="link" :href="route('admin.alerts.index')" variant="secondary" outline icon="arrow-left" label="Voltar" feature="manage-alerts" />
                </div>
            </x-slot:actions>
        </x-layout.page-header>

        <div class="row">
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>Editar Alerta #{{ $alert['id'] }}
                        </h5>
                    </x-slot:header>
                    
                    <form action="{{ route('admin.alerts.update', $alert['id']) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <x-ui.form.select 
                                    name="type" 
                                    label="Tipo de Alerta" 
                                    :options="$alertTypes"
                                    :selected="old('type', $alert['type'])"
                                    required
                                    placeholder="Selecione o tipo..."
                                />
                            </div>
                            
                            <div class="col-md-6">
                                <x-ui.form.select 
                                    name="severity" 
                                    label="Severidade" 
                                    :options="$severities"
                                    :selected="old('severity', $alert['severity'])"
                                    required
                                    placeholder="Selecione a severidade..."
                                    id="severity"
                                />
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <x-ui.form.input 
                                name="title" 
                                label="Título do Alerta" 
                                :value="old('title', $alert['title'])"
                                required
                                placeholder="Digite um título descritivo para o alerta..."
                            />
                        </div>
                        
                        <div class="mb-3">
                            <x-ui.form.textarea 
                                name="message" 
                                label="Mensagem do Alerta" 
                                :value="old('message', $alert['message'])"
                                required
                                rows="4"
                                placeholder="Descreva detalhadamente o que este alerta está reportando..."
                            />
                        </div>
                        
                        <div class="mb-3">
                            <x-ui.form.select 
                                name="status" 
                                label="Status" 
                                :options="[
                                    'active' => 'Ativo',
                                    'resolved' => 'Resolvido'
                                ]"
                                :selected="old('status', $alert['status'])"
                                required
                            />
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <x-ui.button type="link" :href="route('admin.alerts.show', $alert['id'])" variant="secondary" icon="x-circle" label="Cancelar" feature="manage-alerts" />
                            <x-ui.button type="submit" variant="primary" icon="save" label="Atualizar Alerta" feature="manage-alerts" />
                        </div>
                    </form>
                </x-ui.card>
            </div>
        </div>
    </x-layout.page-container>
</x-app-layout>

@push('scripts')
<script>
    // Adicionar validação dinâmica
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const severitySelect = document.getElementById('severity');
        
        // Mudar cor do select baseado na severidade
        if(severitySelect) {
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
        }
        
        // Adicionar confirmação antes de sair da página se houver mudanças
        let formChanged = false;
        if(form) {
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
        }
    });
</script>
@endpush
