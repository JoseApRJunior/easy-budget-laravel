<x-app-layout title="Criar Novo Alerta">
    <x-layout.page-container>
        <x-layout.page-header
            title="Criar Novo Alerta"
            icon="plus-circle"
            :breadcrumb-items="[
                'Admin' => route('admin.dashboard'),
                'Alertas' => route('admin.alerts.index'),
                'Novo Alerta' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button type="link" :href="route('admin.alerts.index')" variant="secondary" outline icon="arrow-left" label="Voltar" feature="manage-alerts" />
            </x-slot:actions>
        </x-layout.page-header>

        <div class="row">
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>Detalhes do Alerta
                        </h5>
                    </x-slot:header>
                    
                    <form action="{{ route('admin.alerts.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <x-ui.form.select 
                                        name="type" 
                                        label="Tipo de Alerta" 
                                        :options="$alertTypes"
                                        :selected="old('type')"
                                        required
                                        placeholder="Selecione o tipo..."
                                    />
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <x-ui.form.select 
                                        name="severity" 
                                        label="Severidade" 
                                        :options="$severities"
                                        :selected="old('severity')"
                                        required
                                        placeholder="Selecione a severidade..."
                                        id="severity"
                                    />
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <x-ui.form.input 
                                name="title" 
                                label="Título do Alerta" 
                                :value="old('title')"
                                required
                                placeholder="Digite um título descritivo para o alerta..."
                            />
                        </div>
                        
                        <div class="mb-3">
                            <x-ui.form.textarea 
                                name="message" 
                                label="Mensagem do Alerta" 
                                :value="old('message')"
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
                                :selected="old('status', 'active')"
                                required
                            />
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <x-ui.button type="link" :href="route('admin.alerts.index')" variant="secondary" icon="x-circle" label="Cancelar" feature="manage-alerts" />
                            <x-ui.button type="submit" variant="primary" icon="save" label="Criar Alerta" feature="manage-alerts" />
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
    });
</script>
@endpush
