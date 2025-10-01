@extends('layouts.app')

@section('title', isset($template) ? 'Editar Template: ' . $template->name : 'Novo Template de Email')

@section('page-title', isset($template) ? 'Editar Template' : 'Novo Template')

@section('content')
<div class="email-template-editor">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('email-templates.index') }}" class="text-gray-700 hover:text-blue-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Email Templates
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <span class="text-gray-500">{{ isset($template) ? 'Editar' : 'Novo' }}</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <form id="templateForm" method="POST" action="{{ isset($template) ? route('email-templates.update', $template) : route('email-templates.store') }}">
        @csrf
        @if(isset($template))
            @method('PUT')
        @endif

        <!-- Configuração do template -->
        <div class="template-config mb-8">
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Configurações do Template</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="templateName" class="block text-sm font-medium text-gray-700 mb-1">
                            Nome do Template *
                        </label>
                        <input type="text"
                               id="templateName"
                               name="name"
                               value="{{ old('name', $template->name ?? '') }}"
                               placeholder="Ex: Confirmação de Orçamento"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="templateCategory" class="block text-sm font-medium text-gray-700 mb-1">
                            Categoria *
                        </label>
                        <select id="templateCategory"
                                name="category"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required>
                            <option value="transactional" {{ (old('category', $template->category ?? '') === 'transactional') ? 'selected' : '' }}>
                                Transacional
                            </option>
                            <option value="promotional" {{ (old('category', $template->category ?? '') === 'promotional') ? 'selected' : '' }}>
                                Promocional
                            </option>
                            <option value="notification" {{ (old('category', $template->category ?? '') === 'notification') ? 'selected' : '' }}>
                                Notificação
                            </option>
                            <option value="system" {{ (old('category', $template->category ?? '') === 'system') ? 'selected' : '' }}>
                                Sistema
                            </option>
                        </select>
                        @error('category')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="templateSubject" class="block text-sm font-medium text-gray-700 mb-1">
                            Assunto *
                        </label>
                        <input type="text"
                               id="templateSubject"
                               name="subject"
                               value="{{ old('subject', $template->subject ?? '') }}"
                               placeholder="Assunto do email"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               required>
                        @error('subject')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Opções avançadas -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-6">
                            <div class="flex items-center">
                                <input type="checkbox"
                                       id="isActive"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <label for="isActive" class="ml-2 text-sm text-gray-700">
                                    Template ativo
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input type="number"
                                       id="sortOrder"
                                       name="sort_order"
                                       value="{{ old('sort_order', $template->sort_order ?? 0) }}"
                                       min="0"
                                       class="w-20 rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <label for="sortOrder" class="ml-2 text-sm text-gray-700">
                                    Ordem
                                </label>
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <button type="button" onclick="previewTemplate()" class="btn btn-secondary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Visualizar
                            </button>
                            <button type="button" onclick="sendTestEmail()" class="btn btn-info">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                Enviar Teste
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Editor visual -->
        <div class="editor-section mb-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Editor -->
                <div class="editor-container">
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <div class="flex items-center justify-between mb-4">
                            <label class="block text-sm font-medium text-gray-700">
                                Conteúdo HTML *
                            </label>
                            <div class="flex space-x-2">
                                <button type="button" onclick="toggleFullscreen()" class="text-sm text-blue-600 hover:text-blue-800">
                                    Tela Cheia
                                </button>
                                <button type="button" onclick="insertVariable()" class="text-sm text-green-600 hover:text-green-800">
                                    Inserir Variável
                                </button>
                            </div>
                        </div>

                        <textarea id="templateEditor"
                                  name="html_content"
                                  class="tinymce w-full border-gray-300 rounded-md shadow-sm"
                                  placeholder="Digite o conteúdo do email...">{{ old('html_content', $template->html_content ?? '') }}</textarea>
                        @error('html_content')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Preview -->
                <div class="preview-container">
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <div class="flex items-center justify-between mb-4">
                            <label class="block text-sm font-medium text-gray-700">
                                Preview
                            </label>
                            <div class="flex space-x-2">
                                <select id="previewDevice" class="text-sm border-gray-300 rounded">
                                    <option value="desktop">Desktop</option>
                                    <option value="tablet">Tablet</option>
                                    <option value="mobile">Mobile</option>
                                </select>
                                <button type="button" onclick="refreshPreview()" class="text-sm text-blue-600 hover:text-blue-800">
                                    Atualizar
                                </button>
                            </div>
                        </div>

                        <div id="previewFrame" class="preview-frame border border-gray-300 rounded-md p-4 h-96 overflow-y-auto bg-white">
                            <div class="email-preview">
                                <!-- Preview será carregado dinamicamente -->
                                <div class="text-center text-gray-500 py-8">
                                    <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <p class="mt-2">Preview será exibido aqui</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Variáveis disponíveis -->
        <div class="variables-section mb-8">
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Variáveis Disponíveis</h3>

                @foreach($availableVariables as $category => $variables)
                    <div class="variable-category mb-6">
                        <h4 class="font-medium text-gray-700 mb-3 flex items-center">
                            <span class="capitalize">{{ $category }}</span>
                            <span class="ml-2 text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                {{ count($variables) }} variáveis
                            </span>
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($variables as $variable => $description)
                                <button type="button"
                                        onclick="insertVariableIntoEditor('{{ '{{' . $variable . '}}' }}')"
                                        class="variable-btn w-full text-left px-3 py-2 text-sm bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded transition-colors">
                                    <div class="flex items-center justify-between">
                                        <code class="text-blue-600 font-mono">{{ '{{' . $variable . '}}' }}</code>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="text-xs text-gray-600 mt-1">{{ $description }}</div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Ações -->
        <div class="actions flex justify-between items-center">
            <div class="flex space-x-3">
                <a href="{{ route('email-templates.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar
                </a>

                @if(isset($template))
                    <button type="button" onclick="duplicateTemplate()" class="btn btn-info">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Duplicar
                    </button>
                @endif
            </div>

            <div class="flex space-x-3">
                <button type="button" onclick="saveDraft()" class="btn btn-secondary">
                    Salvar Rascunho
                </button>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    {{ isset($template) ? 'Atualizar' : 'Criar' }} Template
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal de Envio de Teste -->
<div id="testEmailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Enviar Email de Teste</h3>
                <button onclick="closeTestEmailModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="testEmailForm">
                <div class="mb-4">
                    <label for="testEmail" class="block text-sm font-medium text-gray-700 mb-1">
                        Email de Teste *
                    </label>
                    <input type="email"
                           id="testEmail"
                           placeholder="seu-email@teste.com"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           required>
                </div>

                <div class="mb-4">
                    <label for="testName" class="block text-sm font-medium text-gray-700 mb-1">
                        Nome do Destinatário
                    </label>
                    <input type="text"
                           id="testName"
                           placeholder="Seu Nome"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Dados de Teste (opcional)
                    </label>
                    <div class="space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" placeholder="Nome do Cliente" class="test-data-input rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <input type="text" placeholder="Email do Cliente" class="test-data-input rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" placeholder="Número do Orçamento" class="test-data-input rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <input type="text" placeholder="Valor do Orçamento" class="test-data-input rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeTestEmailModal()" class="btn btn-secondary">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Enviar Teste
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
let tinyMceEditor = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeTinyMCE();
    setupFormValidation();
    setupPreviewUpdates();
});

function initializeTinyMCE() {
    tinymce.init({
        selector: 'textarea#templateEditor',
        height: 500,
        menubar: true,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        setup: function (editor) {
            tinyMceEditor = editor;

            editor.on('change', function () {
                editor.save();
                updatePreview();
            });

            editor.on('keyup', function () {
                updatePreview();
            });
        }
    });
}

function setupFormValidation() {
    const form = document.getElementById('templateForm');

    form.addEventListener('submit', function(e) {
        if (tinyMceEditor) {
            tinyMceEditor.save();
        }

        // Validação básica
        const name = document.getElementById('templateName').value;
        const subject = document.getElementById('templateSubject').value;
        const content = tinyMceEditor ? tinyMceEditor.getContent() : '';

        if (!name.trim()) {
            e.preventDefault();
            alert('Nome do template é obrigatório');
            return false;
        }

        if (!subject.trim()) {
            e.preventDefault();
            alert('Assunto do email é obrigatório');
            return false;
        }

        if (!content.trim()) {
            e.preventDefault();
            alert('Conteúdo HTML é obrigatório');
            return false;
        }
    });
}

function setupPreviewUpdates() {
    // Atualizar preview quando campos de configuração mudarem
    ['templateName', 'templateSubject'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', updatePreview);
    });

    document.getElementById('templateCategory')?.addEventListener('change', updatePreview);
}

function updatePreview() {
    const formData = new FormData(document.getElementById('templateForm'));
    const previewFrame = document.getElementById('previewFrame');

    // Simular dados de teste para preview
    const testData = {
        company_name: 'Easy Budget',
        company_email: 'contato@easybudget.com',
        company_phone: '(11) 99999-9999',
        current_date: new Date().toLocaleDateString('pt-BR'),
        current_year: new Date().getFullYear(),
        customer_name: 'Cliente Teste',
        customer_email: 'cliente@teste.com',
        customer_company: 'Empresa Teste Ltda',
        customer_phone: '(11) 8888-8888',
        user_name: 'Usuário Teste',
        user_email: 'usuario@teste.com',
        user_position: 'Gerente',
        budget_title: 'Orçamento de Desenvolvimento',
        budget_number: 'ORC2024001',
        budget_value: '5.000,00',
        budget_deadline: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toLocaleDateString('pt-BR'),
        budget_items: '• Desenvolvimento de website<br>• Configuração de servidor<br>• Treinamento da equipe',
        budget_link: window.location.origin + '/budgets/test',
        invoice_number: 'FAT2024001',
        invoice_date: new Date().toLocaleDateString('pt-BR'),
        invoice_due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toLocaleDateString('pt-BR'),
        invoice_amount: '5.000,00',
        invoice_link: window.location.origin + '/invoices/test',
    };

    // Processar conteúdo com dados de teste
    let content = tinyMceEditor ? tinyMceEditor.getContent() : '';

    // Substituir variáveis por dados de teste
    Object.entries(testData).forEach(([key, value]) => {
        const regex = new RegExp(`{{\\s*${key}\\s*}}`, 'g');
        content = content.replace(regex, value);
    });

    // Atualizar preview
    const previewDiv = previewFrame.querySelector('.email-preview');
    if (previewDiv) {
        previewDiv.innerHTML = content || '<div class="text-center text-gray-500 py-8"><p>Preview será exibido aqui</p></div>';
    }
}

function previewTemplate() {
    updatePreview();
}

function sendTestEmail() {
    document.getElementById('testEmailModal').classList.remove('hidden');
}

function closeTestEmailModal() {
    document.getElementById('testEmailModal').classList.add('hidden');
}

function insertVariableIntoEditor(variable) {
    if (tinyMceEditor) {
        tinyMceEditor.insertContent(variable);
        tinyMceEditor.focus();
    }
}

function toggleFullscreen() {
    if (tinyMceEditor) {
        tinyMceEditor.execCommand('mceFullScreen');
    }
}

function saveDraft() {
    if (tinyMceEditor) {
        tinyMceEditor.save();
    }

    // Marcar como rascunho
    const isActiveCheckbox = document.getElementById('isActive');
    if (isActiveCheckbox) {
        isActiveCheckbox.checked = false;
    }

    // Submeter formulário
    document.getElementById('templateForm').submit();
}

function duplicateTemplate() {
    if (confirm('Deseja criar uma cópia deste template?')) {
        // Submeter formulário para duplicação
        const form = document.getElementById('templateForm');
        form.action = "{{ isset($template) ? route('email-templates.duplicate', $template) : '#' }}";
        form.method = 'POST';

        // Remover campos que não devem ser duplicados
        const slugField = form.querySelector('[name="slug"]');
        if (slugField) {
            slugField.remove();
        }

        form.submit();
    }
}

// Configurar formulário de teste
document.getElementById('testEmailForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const testEmail = document.getElementById('testEmail').value;
    const testName = document.getElementById('testName').value;

    if (!testEmail) {
        alert('Email de teste é obrigatório');
        return;
    }

    // Coletar dados de teste
    const testDataInputs = document.querySelectorAll('.test-data-input');
    const testData = {};
    testDataInputs.forEach((input, index) => {
        if (input.value) {
            const keys = [
                'customer_name', 'customer_email', 'budget_number', 'budget_value'
            ];
            if (keys[index]) {
                testData[keys[index]] = input.value;
            }
        }
    });

    // Enviar teste via AJAX
    fetch(`{{ isset($template) ? route('api.email-templates.send-test', $template) : '#' }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify({
            test_email: testEmail,
            test_name: testName,
            test_data: testData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Email de teste enviado com sucesso!');
            closeTestEmailModal();
        } else {
            alert('Erro ao enviar teste: ' + data.message);
        }
    })
    .catch(error => {
        alert('Erro interno ao enviar teste');
        console.error(error);
    });
});

// Fechar modal ao clicar fora
document.getElementById('testEmailModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeTestEmailModal();
    }
});
</script>
@endpush

@push('styles')
<style>
.btn {
    @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md transition-colors duration-200;
}

.btn-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700;
}

.btn-secondary {
    @apply bg-gray-200 text-gray-900 hover:bg-gray-300;
}

.btn-info {
    @apply bg-green-600 text-white hover:bg-green-700;
}

.btn-ghost {
    @apply text-gray-600 hover:text-gray-900 hover:bg-gray-100;
}

.variable-btn {
    transition: all 0.2s ease-in-out;
}

.variable-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.preview-frame {
    transition: all 0.3s ease-in-out;
}

#previewDevice {
    background: white;
}

.test-data-input {
    text-sm;
}

.preset-item {
    transition: all 0.2s ease-in-out;
}

.preset-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Responsividade para preview */
@media (max-width: 768px) {
    .editor-section .grid {
        grid-template-columns: 1fr;
    }

    .preview-container {
        order: -1;
    }
}
</style>
@endpush
