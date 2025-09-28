# Diretivas Blade Customizadas - Conversão de Macros Twig

Este documento descreve as diretivas Blade customizadas criadas para substituir as macros Twig do sistema anterior.

## Diretivas Implementadas

### 1. @alert($type, $message)

Substitui a macro `alert` da macro `alerts.twig`.

**Parâmetros:**

-  `$type`: Tipo do alerta (error, success, message, warning)
-  `$message`: Mensagem a ser exibida no alerta

**Uso:**

```blade
@alert('success', 'Operação realizada com sucesso!')
@alert('error', 'Ocorreu um erro ao processar sua solicitação.')
```

**Mapeamento de tipos:**

-  `error` → `danger` (Bootstrap)
-  `success` → `success` (Bootstrap)
-  `message` → `info` (Bootstrap)
-  `warning` → `warning` (Bootstrap)

**HTML Gerado:**

```html
<div
   class="alert alert-success alert-dismissible fade show text-center"
   role="alert"
>
   Mensagem do alerta
   <button
      type="button"
      class="btn-close"
      data-bs-dismiss="alert"
      aria-label="Close"
   ></button>
</div>
```

### 2. @checkFeature($featureSlug, $content, $condition)

Substitui a macro `checkFeature` da macro `utils.twig`.

**Parâmetros:**

-  `$featureSlug`: Slug do recurso a ser verificado
-  `$content`: Conteúdo HTML a ser renderizado
-  `$condition` (opcional): Condição adicional para verificação (padrão: true)

**Uso:**

```blade
{{-- Verificação simples --}}
@checkFeature('reports', '<div>Relatórios disponíveis</div>')

{{-- Com condição adicional --}}
@checkFeature('analytics', '<div>Analytics avançado</div>', $user->hasPermission('analytics'))

{{-- Recurso desativado mostrará aviso --}}
@checkFeature('deprecated-feature', '<div>Este recurso foi descontinuado</div>')
```

**Comportamento:**

-  Se o recurso existe e está ativo: Renderiza apenas o conteúdo
-  Se o recurso existe e está inativo: Mostra aviso + conteúdo com classe `feature-disabled`
-  Se o recurso não existe: Renderiza apenas o conteúdo

**HTML Gerado (recurso inativo):**

```html
<div class="alert alert-warning m-2 d-flex" role="alert">
   <i class="bi bi-exclamation-triangle-fill me-2"></i>
   <div>Recurso desativado temporariamente</div>
</div>
<div class="feature-content feature-disabled">
   <!-- Seu conteúdo aqui -->
</div>
```

## Implementação Técnica

### AppServiceProvider

As diretivas são registradas no `AppServiceProvider.php`:

```php
protected function registerCustomBladeDirectives(): void
{
    Blade::directive('alert', function ($expression) {
        return "<?php echo app('App\Helpers\BladeHelper')->alert({$expression}); ?>";
    });

    Blade::directive('checkFeature', function ($expression) {
        return "<?php echo app('App\Helpers\BladeHelper')->checkFeature({$expression}); ?>";
    });
}
```

### BladeHelper

A classe `App\Helpers\BladeHelper` contém a lógica das diretivas:

```php
class BladeHelper
{
    public function alert(string $type, string $message): string
    {
        // Implementa mapeamento de tipos e gera HTML do alerta
    }

    public function checkFeature(string $featureSlug, string $content, bool $condition = true): string
    {
        // Verifica status do recurso e renderiza conteúdo apropriado
    }

    private function getResource(string $slug): ?Resource
    {
        // Busca recurso pelo slug
    }
}
```

## Recursos Necessários

### Modelo Resource

O sistema utiliza o modelo `App\Models\Resource` com os seguintes campos:

-  `name`: Nome do recurso
-  `slug`: Slug único do recurso
-  `status`: Status (active/inactive/deleted)
-  `in_dev`: Se está em desenvolvimento

### Constantes de Status

```php
Resource::STATUS_ACTIVE   = 'active';
Resource::STATUS_INACTIVE = 'inactive';
Resource::STATUS_DELETED  = 'deleted';
```

## Benefícios da Conversão

1. **Performance**: PHP nativo é mais rápido que Twig
2. **Manutenibilidade**: Código Laravel/Blade padrão
3. **Consistência**: Integrado ao sistema de diretivas do Laravel
4. **Flexibilidade**: Facilita customizações e extensões
5. **Debugging**: Melhor integração com ferramentas do Laravel

## Compatibilidade

As diretivas são totalmente compatíveis com:

-  Bootstrap 5
-  Laravel Blade
-  Sistema de recursos existente
-  Padrões de CSS existentes

## Exemplo Completo

Veja o arquivo `resources/views/example-usage.blade.php` para exemplos práticos de uso das diretivas.
