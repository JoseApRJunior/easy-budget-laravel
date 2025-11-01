# EntityDataService - Guia de Uso

## 📋 Visão Geral

O `EntityDataService` centraliza operações comuns de criação e atualização de dados compartilhados entre **Provider** e **Customer**:
- **CommonData** (nome, documentos, profissão)
- **Contact** (emails, telefones, website)
- **Address** (endereço completo)

## 🎯 Por que usar?

### ✅ Benefícios
- **Reduz duplicação** de código entre Provider e Customer
- **Centraliza validações** e transformações de dados
- **Garante consistência** nas operações
- **Facilita manutenção** - mudanças em um único lugar

### ❌ Quando NÃO usar
- Para campos específicos de User (password, google_id, avatar)
- Para lógica de negócio específica de Provider ou Customer
- Para relacionamentos complexos (hasMany, belongsToMany)

## 🔧 Instalação

O service já está criado em:
```
app/Services/Shared/EntityDataService.php
```

Injete via construtor:
```php
public function __construct(
    private EntityDataService $entityDataService
) {}
```

## 📖 Exemplos de Uso

### 1. Criar dados completos (Provider/Customer)

```php
use App\Services\Shared\EntityDataService;

public function createProvider(array $data, int $tenantId)
{
    // Cria CommonData + Contact + Address em uma transação
    $entityData = $this->entityDataService->createCompleteEntityData($data, $tenantId);
    
    // Cria Provider usando os IDs gerados
    $provider = Provider::create([
        'tenant_id' => $tenantId,
        'user_id' => $userId,
        'common_data_id' => $entityData['common_data']->id,
        'contact_id' => $entityData['contact']->id,
        'address_id' => $entityData['address']->id,
        'terms_accepted' => $data['terms_accepted'],
    ]);
    
    return $provider;
}
```

### 2. Atualizar dados existentes

```php
public function updateProviderBusinessData(array $data)
{
    $user = Auth::user();
    $provider = $user->provider;
    
    // Carrega relacionamentos
    $provider->load(['commonData', 'contact', 'address']);
    
    // Atualiza tudo em uma transação
    $updated = $this->entityDataService->updateCompleteEntityData(
        $provider->commonData,
        $provider->contact,
        $provider->address,
        $data
    );
    
    return $updated;
}
```

### 3. Criar apenas CommonData

```php
$commonData = $this->entityDataService->createCommonData([
    'first_name' => 'João',
    'last_name' => 'Silva',
    'cpf' => '123.456.789-00', // Será limpo automaticamente
    'birth_date' => '01/01/1990', // Será convertido para Y-m-d
], $tenantId);
```

## 🛠️ Helpers Disponíveis

### Validação

```php
validate_cpf('123.456.789-00'); // true/false
validate_cnpj('12.345.678/0001-00'); // true/false
validate_email('email@example.com'); // true/false
validate_phone('(11) 98888-8888'); // true/false
validate_cep('12345-678'); // true/false
```

### Formatação

```php
format_cpf('12345678900'); // 123.456.789-00
format_cnpj('12345678000100'); // 12.345.678/0001-00
format_phone('11988888888'); // (11) 98888-8888
format_cep('12345678'); // 12345-678
clean_document_number('123.456.789-00'); // 12345678900
```

## 🔄 Refatoração do ProviderManagementService

### Antes (código duplicado)

```php
// Atualizar CommonData
if ($provider->commonData) {
    $commonDataUpdate = [
        'company_name' => $data['company_name'] ?? $provider->commonData->company_name,
        'cnpj' => $this->cleanDocumentNumber($data['cnpj'] ?? $provider->commonData->cnpj),
    ];
    $provider->commonData->update($commonDataUpdate);
}
```

### Depois (usando EntityDataService)

```php
$this->entityDataService->updateCompleteEntityData(
    $provider->commonData,
    $provider->contact,
    $provider->address,
    $data
);
```

## 🎓 Boas Práticas

### ✅ Faça

```php
// Use o service para operações comuns
$entityData = $this->entityDataService->createCompleteEntityData($data, $tenantId);

// Use helpers para validação
if (validate_cpf($data['cpf'])) {
    // Salvar
}
```

### ❌ Não faça

```php
// Não duplique lógica de limpeza
$cpf = preg_replace('/[^0-9]/', '', $data['cpf']); // Use clean_document_number()

// Não crie manualmente quando puder usar o service
CommonData::create([...]); // Use $entityDataService->createCommonData()
```
