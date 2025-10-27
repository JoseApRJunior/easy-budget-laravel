# Tarefas & Notas

## Autenticação & Experiência do Usuário

-  [x] Após registro social, enviar email de boas-vindas
   -  Solicitar alteração de senha para login não-Google
   -  Conta é pré-verificada e ativa

## Banco de Dados

-  [ ] Migrar tabelas de status para enums

## Melhorias de Interface

-  [x] Atualizar estilo do campo de senha em todas as páginas:
   -  `/register`
   -  `/provider/change-password`
   -  Manter alinhamento consistente dos campos

## Refatoração de Código

### Sistema de Email

-  [x] Revisar `AbstractBaseConfirmationEmail.php`:
   -  Remover geração redundante do link de confirmação
   -  Limpar código obsoleto
   -  Consolidar lógica duplicada com `SendWelcomeEmail.php`
   -  Considerar uso direto do link ao invés de geração

```php
// Implementação atual para revisão
return array_merge($this->getUserBasicData(), [
    'confirmationLink' => $this->confirmationLink ?? $this->generateConfirmationLink(),
    'tenant_name'     => $this->tenant?->name ?? 'Easy Budget',
]);
```

Error
Class "Intervention\Image\Facades\Image" not found
PATCH dev.easybudget.net.br
PHP 8.2.12 — Laravel 12.28.1
C:\xampp\htdocs\easy-budget-laravel\app\Services\Application\FileUploadService.php:259

    {
        $originalPath = storage_path( "app/public/avatars/{$tenantId}/{$filename}" );
        $thumbPath    = storage_path( "app/public/avatars/{$tenantId}/thumb_{$size}_{$filename}" );

        // Cria thumbnail quadrado
        $image = Image::make( $file->getPathname() );
        $image->fit( $size, $size );
        $image->save( $thumbPath, 90 ); // 90% qualidade

        return "avatars/{$tenantId}/thumb_{$size}_{$filename}";
    }
