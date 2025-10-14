# Sistema de Internacionalização de E-mails

Este documento descreve o sistema completo de internacionalização implementado para os e-mails do Easy Budget Laravel.

## 📋 Funcionalidades Implementadas

### ✅ Arquivos de Internacionalização

-  **Português (Brasil):** `resources/lang/pt-BR/emails.php`
-  **Inglês:** `resources/lang/en/emails.php`
-  **Estrutura completa** com todas as mensagens de e-mail
-  **Sistema de fallback** automático para locale padrão

### ✅ Componentes Reutilizáveis

-  **Button:** Botões estilizados com diferentes cores e tamanhos
-  **Panel:** Painéis informativos com títulos
-  **Alert:** Alertas coloridos (success, error, warning, info)
-  **Table:** Tabelas responsivas para dados tabulares
-  **Badge:** Labels pequenas para status e categorias

### ✅ Sistema de Preview

-  **Interface web** para visualizar e-mails antes do envio
-  **Seleção dinâmica** de idioma
-  **Visualização responsiva** (mobile, tablet, desktop)
-  **Dados de exemplo** para facilitar testes

### ✅ Serviço de Localização

-  **EmailLocalizationService** para gerenciamento centralizado
-  **Formatação automática** de moeda e data por locale
-  **Cache inteligente** de configurações
-  **Tratamento robusto** de erros de tradução

## 🚀 Como Usar

### 1. Básico - Tradução Simples

```php
// Em qualquer lugar do código
use App\Services\Infrastructure\EmailLocalizationService;

$localization = new EmailLocalizationService();
$text = $localization->translate('emails.verification.subject', [
    'app_name' => config('app.name')
], 'pt-BR');
```

### 2. Em Mailables

```php
<?php

class ExampleMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct($locale = 'pt-BR')
    {
        $this->locale = $locale;
        app()->setLocale($locale);
    }

    public function build()
    {
        return $this->subject(__('emails.example.subject', [], $this->locale))
                    ->view('emails.example', [
                        'locale' => $this->locale,
                        'data' => $this->data
                    ]);
    }
}
```

### 3. Em Templates Blade

```blade
{{-- Tradução simples --}}
{{ __('emails.verification.greeting', ['name' => $userName], $locale) }}

{{-- Formatação de moeda --}}
{{ $localization->formatCurrency($amount, $locale) }}

{{-- Formatação de data --}}
{{ $localization->formatDate($date, $locale) }}
```

### 4. Usando Componentes

```blade
{{-- Botão primário --}}
<x-emails.components.button :url="$url" color="primary" size="large">
    {{ __('emails.common.button_view', [], $locale) }}
</x-emails.components.button>

{{-- Painel informativo --}}
<x-emails.components.panel title="{{ __('emails.budget.details', [], $locale) }}">
    {!! $content !!}
</x-emails.components.panel>

{{-- Alerta de sucesso --}}
<x-emails.components.alert type="success" :message="__('emails.common.success', [], $locale)" />

{{-- Tabela de dados --}}
<x-emails.components.table :headers="$headers" :rows="$rows" :striped="true" />

{{-- Badge de status --}}
<x-emails.components.badge :text="__('emails.budget.status.approved', [], $locale)" type="success" />
```

## 🌐 Estrutura de Internacionalização

### Arquivos de Idioma

```
resources/lang/
├── pt-BR/
│   └── emails.php    # Português brasileiro
└── en/
    └── emails.php    # Inglês
```

### Seções Disponíveis

#### Verificação de E-mail (`verification`)

-  `subject` - Assunto do e-mail
-  `greeting` - Saudação
-  `line1` - Primeira linha de texto
-  `button` - Texto do botão
-  `details` - Detalhes da confirmação
-  `help` - Texto de ajuda

#### Orçamentos (`budget`)

-  `subject` - Assuntos por tipo (created, updated, approved, etc.)
-  `greeting` - Saudação
-  `line1` - Mensagens por tipo
-  `details` - Detalhes do orçamento
-  `button` - Texto do botão

#### Faturas (`invoice`)

-  `subject` - Assunto da fatura
-  `greeting` - Saudação
-  `line1` - Primeira linha
-  `details` - Detalhes da fatura
-  `button` - Texto do botão

#### Comum (`common`)

-  Botões (`button_*`)
-  Estados (`loading`, `error`, `success`)
-  Campos (`name`, `email`, `phone`, etc.)
-  Formatação (`currency`, `date_format`)

## 🔧 Sistema de Preview

### Acesso

```
URL: /email-preview
Rotas:
- GET /email-preview - Lista de tipos de e-mail
- GET /email-preview/{tipo} - Preview específico
- GET /email-preview/config/data - Configurações
```

### Recursos

-  **Seleção de idioma** em tempo real
-  **Visualização responsiva** (mobile/tablet/desktop)
-  **Dados de exemplo** para facilitar testes
-  **Informações técnicas** (assunto, locale, timestamp)

## ⚙️ Configuração

### Locale Padrão

```php
// Em config/app.php
'locale' => env('APP_LOCALE', 'pt-BR'),
'fallback_locale' => 'pt-BR',
```

### Cache de Tradução

```php
// TTL do cache (1 hora)
EmailLocalizationService::CACHE_TTL = 3600;

// Limpar cache
$localization = new EmailLocalizationService();
$localization->clearLocaleCache();
```

## 🎨 Personalização

### Adicionar Novo Idioma

1. **Criar arquivo de idioma:**

```bash
mkdir -p resources/lang/es
touch resources/lang/es/emails.php
```

2. **Adicionar traduções:**

```php
<?php
// resources/lang/es/emails.php
return [
    'verification' => [
        'subject' => 'Verificación de Correo - :app_name',
        'greeting' => 'Hola :name,',
        // ... outras traduções
    ],
    // ... outras seções
];
```

3. **Atualizar serviço:**

```php
// O sistema detectará automaticamente o novo idioma
$localization->getSupportedLocales(); // Incluirá 'es'
```

### Criar Novo Componente

1. **Criar arquivo em `resources/views/emails/components/`:**

```php
{{-- resources/views/emails/components/card.blade.php --}}
@php
    $title = $title ?? '';
    $content = $content ?? '';
    $class = $class ?? 'card-default';
@endphp

<div class="email-card {{ $class }}" style="border: 1px solid #ddd; padding: 20px; margin: 10px 0;">
    @if($title)
        <h3 style="margin-top: 0;">{{ $title }}</h3>
    @endif
    {!! $content !!}
</div>
```

2. **Usar no template:**

```blade
<x-emails.components.card title="Título do Card">
    <p>Conteúdo do card...</p>
</x-emails.components.card>
```

## 🔍 Troubleshooting

### Problemas Comuns

#### Tradução não encontrada

```php
// Verificar se a chave existe
$translation = __('emails.verification.subject', [], 'pt-BR');

// Verificar locale suportado
$localization = new EmailLocalizationService();
$supported = $localization->getSupportedLocales();
```

#### Cache de tradução

```php
// Limpar cache se necessário
$localization = new EmailLocalizationService();
$localization->clearLocaleCache();
```

#### Formatação incorreta

```php
// Usar o serviço de localização para formatação
$localization = new EmailLocalizationService();
$formatted = $localization->formatCurrency(1234.56, 'pt-BR');
// Resultado: "R$ 1.234,56"
```

## 📊 Monitoramento

### Estatísticas de Tradução

```php
$localization = new EmailLocalizationService();
$stats = $localization->getTranslationStats();
// Retorna: total_requests, successful_translations, fallback_used, etc.
```

### Logs de Erro

```php
// Verificar logs para problemas de tradução
tail -f storage/logs/laravel.log | grep "translation"
```

## 🚀 Próximos Passos

### Melhorias Planejadas

-  [ ] Adicionar mais idiomas (espanhol, francês)
-  [ ] Implementar componentes avançados (accordion, tabs)
-  [ ] Sistema de A/B testing para templates
-  [ ] Analytics de abertura e cliques por idioma
-  [ ] Templates específicos por tenant/empresa

### Manutenção

-  [ ] Revisar traduções periodicamente
-  [ ] Atualizar componentes conforme necessidade
-  [ ] Monitorar performance do sistema de cache
-  [ ] Documentar novos componentes criados

---

**Sistema implementado em:** {{ date('d/m/Y H:i:s') }}
**Versão:** 1.0.0
**Status:** ✅ **Produção**
