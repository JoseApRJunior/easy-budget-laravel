# Padrões de Design - Service Layer

Este diretório contém exemplos de implementação dos padrões de service layer para o projeto Easy Budget. Os serviços seguem a arquitetura em camadas com princípios SOLID e Clean Architecture, utilizando o framework Laravel.

## BaseTenantService vs BaseNoTenantService

### BaseTenantService
- **Uso**: Para entidades que pertencem a um tenant específico (multi-tenancy)
- **Características**:
  - Sempre filtra dados pelo `tenant_id`
  - Implementa métodos específicos de tenant como `findByIdAndTenantId()`, `findAllByTenantId()`
  - Usa `RepositoryInterface` com suporte a multi-tenancy
  - Ideal para entidades como `User`, `Budget`, `Subscription`
- **Exemplo**: [WithTenant/ExampleService.php](WithTenant/ExampleService.php)

### BaseNoTenantService
- **Uso**: Para entidades globais que não dependem de tenant (configurações do sistema, logs, etc.)
- **Características**:
  - Opera sem contexto de tenant
  - Implementa métodos CRUD padrão sem filtros de tenant
  - Usa `RepositoryNoTenantInterface` quando aplicável
  - Ideal para entidades como `SystemConfig`, `AuditLog`, `GlobalSettings`
- **Exemplo**: [NoTenant/ExampleService.php](NoTenant/ExampleService.php)

## Uso de ServiceResult

Todos os serviços retornam instâncias de `ServiceResult` para padronizar respostas:

```php
// Sucesso
$result = $service->create($data);
if ($result->isSuccess()) {
    $entity = $result->getData();
    $metadata = $result->getMetadata(); // array com mensagens, totals, etc.
}

// Erro
if ($result->isError()) {
    $message = $result->getMessage();
    $errors = $result->getErrors(); // array de erros de validação
}
```

### Padrões Modernos de Retorno Laravel
- **Padronização**: Sempre retornar `ServiceResult` em vez de valores diretos
- **Metadata**: Segundo parâmetro do `success()` aceita array para informações adicionais
- **Validação**: `ValidationException` é capturada e convertida em `ServiceResult` com erros detalhados
- **Transações**: Operações críticas usam transações DB automaticamente via base service

## Validação, Transações e Best Practices

### Validação
```php
// No método validateData() - sempre implementar
private function validateData(array $data, string $operation): void
{
    $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
    ];

    $validator = Validator::make($data, $rules);
    if ($validator->fails()) {
        throw new ValidationException($validator);
    }
}
```

### Transações (Automáticas na Base)
```php
// A BaseService gerencia transações automaticamente
// Para operações manuais, use:
\DB::transaction(function () use ($data) {
    $entity = $this->createEntity($data);
    // outras operações...
});
```

### Best Practices

#### 1. Implementação de Métodos Abstratos
```php
// SEMPRE implementar todos os métodos abstratos da base
class ExampleService extends BaseNoTenantService
{
    protected function findEntityById(int $tenantId): ?Model { ... }
    protected function listEntities(?array $orderBy = null, ?int $limit = null): array { ... }
    protected function createEntity(array $data): Model { ... }
    protected function updateEntity(int $tenantId, array $data): ?Model { ... }
    protected function canDeleteEntity(int $tenantId): bool { ... }
    protected function deleteEntity(int $tenantId): bool { ... }
}
```

#### 2. Tratamento de Exceções
```php
public function create(array $data): ServiceResult
{
    try {
        $entity = $this->createEntity($data);
        return $this->success($entity, ['message' => 'Criado com sucesso']);
    } catch (ValidationException $e) {
        return $this->error('Validação falhou', $e->errors());
    } catch (\Exception $e) {
        return $this->error('Erro interno: ' . $e->getMessage());
    }
}
```

#### 3. Filtros Seguros
```php
private function applyFilters(Builder $query, array $filters): Builder
{
    foreach ($filters as $key => $value) {
        if (!empty($value) && $this->isValidFilter($key)) {
            $query->where($key, $value);
        }
    }
    return $query;
}
```

#### 4. Injeção de Dependência
```php
// No constructor ou via container
public function __construct(
    protected RepositoryInterface $repository,
    protected LoggerInterface $logger = null
) {
    // Inicialização
}
```

## Snippets de Código

### Exemplo WithTenant Service
```php
<?php
namespace App\Services;

use App\Services\Abstracts\BaseTenantService;
use App\Repositories\UserRepository;

class UserService extends BaseTenantService
{
    protected string $entityName = 'User';

    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    public function findByEmailAndTenant(string $email): ServiceResult
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $user = $this->repository->findByEmailAndTenantId($email, $tenantId);

            return $user
                ? $this->success($user)
                : $this->error('Usuário não encontrado');
        } catch (\Exception $e) {
            return $this->error('Erro ao buscar usuário: ' . $e->getMessage());
        }
    }
}
```

### Exemplo NoTenant Service
```php
<?php
namespace App\Services;

use App\Services\Abstracts\BaseNoTenantService;
use App\Repositories\SystemConfigRepository;

class SystemConfigService extends BaseNoTenantService
{
    protected string $entityName = 'SystemConfig';

    public function getGlobalSetting(string $key): ServiceResult
    {
        try {
            $config = $this->findEntityById($key);

            return $config
                ? $this->success($config->value)
                : $this->error('Configuração não encontrada');
        } catch (\Exception $e) {
            return $this->error('Erro ao buscar configuração: ' . $e->getMessage());
        }
    }

    public function updateGlobalSetting(string $key, string $value): ServiceResult
    {
        try {
            $config = $this->findEntityById($key);
            if (!$config) {
                return $this->error('Configuração não encontrada');
            }

            $config->value = $value;
            $this->repository->save($config);

            return $this->success($config, ['message' => 'Configuração atualizada']);
        } catch (\Exception $e) {
            return $this->error('Erro ao atualizar configuração: ' . $e->getMessage());
        }
    }
}
```

## Cross-References

- **WithTenant Example**: [WithTenant/ExampleService.php](WithTenant/ExampleService.php) - Demonstra serviços com contexto de tenant
- **NoTenant Example**: [NoTenant/ExampleService.php](NoTenant/ExampleService.php) - Demonstra serviços globais sem tenant
- **Base Services**:
  - [BaseTenantService](../../../Services/Abstracts/BaseTenantService.php)
  - [BaseNoTenantService](../../../Services/Abstracts/BaseNoTenantService.php)
- **ServiceResult**: [ServiceResult](../../../Support/ServiceResult.php)
- **Repository Interfaces**:
  - [RepositoryInterface](../../../Interfaces/RepositoryInterface.php)
  - [RepositoryNoTenantInterface](../../../Interfaces/RepositoryNoTenantInterface.php)

## Verificação de Compilação

Para verificar se ambos os exemplos compilam corretamente:

```bash
# Verificar sintaxe PHP
php -l app/DesignPatterns/WithTenant/ExampleService.php
php -l app/DesignPatterns/NoTenant/ExampleService.php

# Análise estática com PHPStan (se configurado)
vendor/bin/phpstan analyse app/DesignPatterns/ --level=5

# Verificar estilo com PHP-CS-Fixer
vendor/bin/php-cs-fixer fix --dry-run --diff app/DesignPatterns/
```

## Próximos Passos

1. **Testes Unitários**: Criar testes para ambos os exemplos
2. **Integração**: Usar estes padrões em serviços reais do projeto
3. **Documentação API**: Gerar Swagger docs para endpoints que usam estes serviços
4. **Performance**: Monitorar performance com New Relic ou similar
5. **Segurança**: Auditar validações e autorizações implementadas

---

*Última atualização: 19 de Setembro de 2025*
*Versão do projeto: Easy Budget Laravel v2.x*
