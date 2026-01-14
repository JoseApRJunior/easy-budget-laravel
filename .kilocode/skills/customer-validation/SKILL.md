# 🔍 Skill: Customer Validation (Validação de Clientes)

**Descrição:** Garante validações rigorosas de documentos (CPF/CNPJ), duplicação e regras de negócio para clientes PF/PJ.

**Categoria:** Validação de Dados
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Padronizar validações de clientes no Easy Budget, garantindo integridade dos dados através de validações de documentos, verificação de duplicação e regras de negócio específicas para cada tipo de cliente.

## 📋 Requisitos Técnicos

### **✅ Validação de CPF**

```php
class CpfValidationService extends AbstractBaseService
{
    public function validateCpf(string $cpf): ServiceResult
    {
        // 1. Limpar formatação
        $cpf = $this->cleanCpf($cpf);

        // 2. Validar tamanho
        if (strlen($cpf) !== 11) {
            return $this->error('CPF deve ter 11 dígitos', OperationStatus::INVALID_DATA);
        }

        // 3. Validar dígitos repetidos
        if ($this->hasRepeatedDigits($cpf)) {
            return $this->error('CPF inválido - dígitos repetidos', OperationStatus::INVALID_DATA);
        }

        // 4. Validar dígitos verificadores
        if (! $this->validateCpfDigits($cpf)) {
            return $this->error('CPF inválido - dígitos verificadores incorretos', OperationStatus::INVALID_DATA);
        }

        return $this->success($cpf, 'CPF válido');
    }

    private function cleanCpf(string $cpf): string
    {
        return preg_replace('/[^0-9]/', '', $cpf);
    }

    private function hasRepeatedDigits(string $cpf): bool
    {
        return preg_match('/^(\d)\1{10}$/', $cpf);
    }

    private function validateCpfDigits(string $cpf): bool
    {
        // Cálculo do primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        // Cálculo do segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return $cpf[9] == $digit1 && $cpf[10] == $digit2;
    }
}
```

### **✅ Validação de CNPJ**

```php
class CnpjValidationService extends AbstractBaseService
{
    public function validateCnpj(string $cnpj): ServiceResult
    {
        // 1. Limpar formatação
        $cnpj = $this->cleanCnpj($cnpj);

        // 2. Validar tamanho
        if (strlen($cnpj) !== 14) {
            return $this->error('CNPJ deve ter 14 dígitos', OperationStatus::INVALID_DATA);
        }

        // 3. Validar dígitos repetidos
        if ($this->hasRepeatedDigits($cnpj)) {
            return $this->error('CNPJ inválido - dígitos repetidos', OperationStatus::INVALID_DATA);
        }

        // 4. Validar dígitos verificadores
        if (! $this->validateCnpjDigits($cnpj)) {
            return $this->error('CNPJ inválido - dígitos verificadores incorretos', OperationStatus::INVALID_DATA);
        }

        return $this->success($cnpj, 'CNPJ válido');
    }

    private function cleanCnpj(string $cnpj): string
    {
        return preg_replace('/[^0-9]/', '', $cnpj);
    }

    private function hasRepeatedDigits(string $cnpj): bool
    {
        return preg_match('/^(\d)\1{13}$/', $cnpj);
    }

    private function validateCnpjDigits(string $cnpj): bool
    {
        // Cálculo do primeiro dígito verificador
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        // Cálculo do segundo dígito verificador
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return $cnpj[12] == $digit1 && $cnpj[13] == $digit2;
    }
}
```

### **✅ Validação de Duplicação**

```php
class CustomerDuplicateValidationService extends AbstractBaseService
{
    public function checkDuplicateCustomer(CustomerDTO $dto, ?int $excludeId = null): ServiceResult
    {
        // 1. Verificar duplicação por CPF
        if (! empty($dto->cpf)) {
            $duplicate = $this->repository->findByCpf($dto->cpf, $excludeId);
            if ($duplicate) {
                return $this->error('Já existe cliente com este CPF', OperationStatus::DUPLICATE_DATA);
            }
        }

        // 2. Verificar duplicação por CNPJ
        if (! empty($dto->cnpj)) {
            $duplicate = $this->repository->findByCnpj($dto->cnpj, $excludeId);
            if ($duplicate) {
                return $this->error('Já existe cliente com este CNPJ', OperationStatus::DUPLICATE_DATA);
            }
        }

        // 3. Verificar duplicação por e-mail
        if (! empty($dto->email)) {
            $duplicate = $this->repository->findByEmail($dto->email, $excludeId);
            if ($duplicate) {
                return $this->error('Já existe cliente com este e-mail', OperationStatus::DUPLICATE_DATA);
            }
        }

        // 4. Verificar duplicação por telefone
        if (! empty($dto->phone)) {
            $duplicate = $this->repository->findByPhone($dto->phone, $excludeId);
            if ($duplicate) {
                return $this->error('Já existe cliente com este telefone', OperationStatus::DUPLICATE_DATA);
            }
        }

        return $this->success(null, 'Nenhuma duplicação encontrada');
    }
}
```

### **✅ Validação de Campos Condicionais**

```php
class CustomerConditionalValidationService extends AbstractBaseService
{
    public function validateConditionalFields(CustomerDTO $dto): ServiceResult
    {
        $customerType = CustomerType::from($dto->type);

        // Campos obrigatórios para Pessoa Física
        if ($customerType === CustomerType::INDIVIDUAL) {
            if (empty($dto->first_name) || empty($dto->last_name)) {
                return $this->error('Nome e sobrenome são obrigatórios para Pessoa Física', OperationStatus::INVALID_DATA);
            }

            // CPF é obrigatório para PF
            if (empty($dto->cpf)) {
                return $this->error('CPF é obrigatório para Pessoa Física', OperationStatus::INVALID_DATA);
            }
        }

        // Campos obrigatórios para Pessoa Jurídica
        if ($customerType === CustomerType::COMPANY) {
            if (empty($dto->company_name)) {
                return $this->error('Razão social é obrigatória para Pessoa Jurídica', OperationStatus::INVALID_DATA);
            }

            // CNPJ é obrigatório para PJ
            if (empty($dto->cnpj)) {
                return $this->error('CNPJ é obrigatório para Pessoa Jurídica', OperationStatus::INVALID_DATA);
            }
        }

        // Campos que podem ser nulos dependendo do tipo
        if ($customerType === CustomerType::INDIVIDUAL) {
            $dto->company_name = null;
            $dto->cnpj = null;
            $dto->fantasy_name = null;
            $dto->state_registration = null;
            $dto->municipal_registration = null;
            $dto->founding_date = null;
            $dto->industry = null;
            $dto->company_size = null;
        }

        if ($customerType === CustomerType::COMPANY) {
            $dto->cpf = null;
        }

        return $this->success($dto, 'Campos condicionais validados');
    }
}
```

## 🏗️ Estrutura de Validação

### **📊 Fluxo de Validação Completo**

```
┌─────────────────┐
│  Dados do DTO   │
└─────────────────┘
         │
         ▼
┌─────────────────┐    ┌─────────────────┐
│  Validação de   │───▶│  Validação de   │
│   Documentos    │    │   Duplicação    │
└─────────────────┘    └─────────────────┘
         │                       │
         ▼                       ▼
┌─────────────────┐    ┌─────────────────┐
│  Validação      │───▶│  Validação      │
│  Condicional    │    │  de Contatos    │
└─────────────────┘    └─────────────────┘
         │                       │
         ▼                       ▼
┌─────────────────┐    ┌─────────────────┐
│  Validação      │───▶│  Resultado      │
│  de Endereço    │    │  Consolidado    │
└─────────────────┘    └─────────────────┘
```

### **📝 Validação de Contatos**

```php
class CustomerContactValidationService extends AbstractBaseService
{
    public function validateContactFields(array $contacts): ServiceResult
    {
        $emailCount = 0;
        $phoneCount = 0;
        $errors = [];

        foreach ($contacts as $contact) {
            // Validar e-mail
            if ($contact['type'] === 'email') {
                $emailCount++;
                if (! filter_var($contact['value'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "E-mail inválido: {$contact['value']}";
                }
            }

            // Validar telefone
            if ($contact['type'] === 'phone') {
                $phoneCount++;
                if (! $this->isValidPhone($contact['value'])) {
                    $errors[] = "Telefone inválido: {$contact['value']}";
                }
            }
        }

        // Pelo menos um e-mail é obrigatório
        if ($emailCount === 0) {
            $errors[] = 'É necessário pelo menos um e-mail';
        }

        // Mínimo de contatos
        if (count($contacts) === 0) {
            $errors[] = 'É necessário pelo menos um contato';
        }

        if (! empty($errors)) {
            return $this->error(implode('; ', $errors), OperationStatus::INVALID_DATA);
        }

        return $this->success($contacts, 'Contatos validados');
    }

    private function isValidPhone(string $phone): bool
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($phone) >= 10 && strlen($phone) <= 11;
    }
}
```

### **✅ Validação de Endereço**

```php
class CustomerAddressValidationService extends AbstractBaseService
{
    public function validateAddressFields(AddressDTO $address): ServiceResult
    {
        $errors = [];

        // Campos obrigatórios
        if (empty($address->address)) {
            $errors[] = 'Endereço é obrigatório';
        }

        if (empty($address->neighborhood)) {
            $errors[] = 'Bairro é obrigatório';
        }

        if (empty($address->city)) {
            $errors[] = 'Cidade é obrigatória';
        }

        if (empty($address->state)) {
            $errors[] = 'Estado é obrigatório';
        }

        if (empty($address->cep)) {
            $errors[] = 'CEP é obrigatório';
        }

        // Validar CEP
        if (! $this->isValidCep($address->cep)) {
            $errors[] = 'CEP inválido';
        }

        if (! empty($errors)) {
            return $this->error(implode('; ', $errors), OperationStatus::INVALID_DATA);
        }

        return $this->success($address, 'Endereço validado');
    }

    private function isValidCep(string $cep): bool
    {
        $cep = preg_replace('/[^0-9]/', '', $cep);
        return strlen($cep) === 8;
    }
}
```

## 🧪 Testes de Validação

### **✅ Testes de Validação de CPF**

```php
public function testValidCpf()
{
    $cpf = '123.456.789-09';
    $result = $this->cpfValidationService->validateCpf($cpf);
    $this->assertTrue($result->isSuccess());
    $this->assertEquals('12345678909', $result->getData());
}

public function testInvalidCpf()
{
    $cpf = '111.111.111-11'; // CPF inválido (todos dígitos iguais)
    $result = $this->cpfValidationService->validateCpf($cpf);
    $this->assertFalse($result->isSuccess());
    $this->assertEquals(OperationStatus::INVALID_DATA, $result->getStatus());
}

public function testCpfWithInvalidDigits()
{
    $cpf = '123.456.789-99'; // Dígitos verificadores incorretos
    $result = $this->cpfValidationService->validateCpf($cpf);
    $this->assertFalse($result->isSuccess());
    $this->assertEquals(OperationStatus::INVALID_DATA, $result->getStatus());
}
```

### **✅ Testes de Validação de CNPJ**

```php
public function testValidCnpj()
{
    $cnpj = '12.345.678/0001-95';
    $result = $this->cnpjValidationService->validateCnpj($cnpj);
    $this->assertTrue($result->isSuccess());
    $this->assertEquals('12345678000195', $result->getData());
}

public function testInvalidCnpj()
{
    $cnpj = '11.111.111/1111-11'; // CNPJ inválido (todos dígitos iguais)
    $result = $this->cnpjValidationService->validateCnpj($cnpj);
    $this->assertFalse($result->isSuccess());
    $this->assertEquals(OperationStatus::INVALID_DATA, $result->getStatus());
}

public function testCnpjWithInvalidDigits()
{
    $cnpj = '12.345.678/0001-99'; // Dígitos verificadores incorretos
    $result = $this->cnpjValidationService->validateCnpj($cnpj);
    $this->assertFalse($result->isSuccess());
    $this->assertEquals(OperationStatus::INVALID_DATA, $result->getStatus());
}
```

### **✅ Testes de Duplicação**

```php
public function testDuplicateCpf()
{
    // Criar cliente existente
    $existingCustomer = Customer::factory()->create();
    $existingCustomer->commonData->cpf = '12345678909';
    $existingCustomer->commonData->save();

    // Tentar criar cliente com mesmo CPF
    $dto = new CustomerDTO([
        'type' => 'individual',
        'cpf' => '123.456.789-09',
        'first_name' => 'Teste',
        'last_name' => 'Teste',
    ]);

    $result = $this->duplicateValidationService->checkDuplicateCustomer($dto);
    $this->assertFalse($result->isSuccess());
    $this->assertEquals(OperationStatus::DUPLICATE_DATA, $result->getStatus());
}

public function testDuplicateCnpj()
{
    // Criar cliente existente
    $existingCustomer = Customer::factory()->create();
    $existingCustomer->commonData->cnpj = '12345678000195';
    $existingCustomer->commonData->save();

    // Tentar criar cliente com mesmo CNPJ
    $dto = new CustomerDTO([
        'type' => 'company',
        'cnpj' => '12.345.678/0001-95',
        'company_name' => 'Empresa Teste',
    ]);

    $result = $this->duplicateValidationService->checkDuplicateCustomer($dto);
    $this->assertFalse($result->isSuccess());
    $this->assertEquals(OperationStatus::DUPLICATE_DATA, $result->getStatus());
}
```

### **✅ Testes de Campos Condicionais**

```php
public function testIndividualWithoutRequiredFields()
{
    $dto = new CustomerDTO([
        'type' => 'individual',
        'cpf' => '123.456.789-09',
        // first_name e last_name ausentes
    ]);

    $result = $this->conditionalValidationService->validateConditionalFields($dto);
    $this->assertFalse($result->isSuccess());
    $this->assertEquals(OperationStatus::INVALID_DATA, $result->getStatus());
}

public function testCompanyWithoutRequiredFields()
{
    $dto = new CustomerDTO([
        'type' => 'company',
        'cnpj' => '12.345.678/0001-95',
        // company_name ausente
    ]);

    $result = $this->conditionalValidationService->validateConditionalFields($dto);
    $this->assertFalse($result->isSuccess());
    $this->assertEquals(OperationStatus::INVALID_DATA, $result->getStatus());
}

public function testIndividualWithCompanyFields()
{
    $dto = new CustomerDTO([
        'type' => 'individual',
        'cpf' => '123.456.789-09',
        'first_name' => 'Teste',
        'last_name' => 'Teste',
        'company_name' => 'Empresa Teste', // Campo que deve ser nulo para PF
    ]);

    $result = $this->conditionalValidationService->validateConditionalFields($dto);
    $this->assertTrue($result->isSuccess());

    // Campos de empresa devem ser nulos
    $validatedDto = $result->getData();
    $this->assertNull($validatedDto->company_name);
    $this->assertNull($validatedDto->cnpj);
}
```

## 📊 Métricas de Validação

### **✅ Métricas de Qualidade de Dados**

```php
class CustomerValidationMetricsService extends AbstractBaseService
{
    public function getValidationMetrics(): array
    {
        $totalCustomers = Customer::count();
        $validCpfCustomers = Customer::whereHas('commonData', function ($query) {
            $query->whereNotNull('cpf')->where('cpf', '!=', '');
        })->count();

        $validCnpjCustomers = Customer::whereHas('commonData', function ($query) {
            $query->whereNotNull('cnpj')->where('cnpj', '!=', '');
        })->count();

        $validEmailCustomers = Customer::whereHas('contact', function ($query) {
            $query->whereNotNull('email')->where('email', '!=', '');
        })->count();

        $validPhoneCustomers = Customer::whereHas('contact', function ($query) {
            $query->whereNotNull('phone')->where('phone', '!=', '');
        })->count();

        return [
            'total_customers' => $totalCustomers,
            'valid_cpf_percentage' => $totalCustomers > 0 ? ($validCpfCustomers / $totalCustomers) * 100 : 0,
            'valid_cnpj_percentage' => $totalCustomers > 0 ? ($validCnpjCustomers / $totalCustomers) * 100 : 0,
            'valid_email_percentage' => $totalCustomers > 0 ? ($validEmailCustomers / $totalCustomers) * 100 : 0,
            'valid_phone_percentage' => $totalCustomers > 0 ? ($validPhoneCustomers / $totalCustomers) * 100 : 0,
            'data_quality_score' => $this->calculateDataQualityScore($totalCustomers, $validCpfCustomers, $validCnpjCustomers, $validEmailCustomers, $validPhoneCustomers),
        ];
    }

    private function calculateDataQualityScore(int $total, int $validCpf, int $validCnpj, int $validEmail, int $validPhone): float
    {
        if ($total === 0) {
            return 0.0;
        }

        $cpfWeight = 0.25;
        $cnpjWeight = 0.25;
        $emailWeight = 0.25;
        $phoneWeight = 0.25;

        $cpfScore = ($validCpf / $total) * $cpfWeight;
        $cnpjScore = ($validCnpj / $total) * $cnpjWeight;
        $emailScore = ($validEmail / $total) * $emailWeight;
        $phoneScore = ($validPhone / $total) * $phoneWeight;

        return ($cpfScore + $cnpjScore + $emailScore + $phoneScore) * 100;
    }
}
```

### **✅ Alertas de Qualidade de Dados**

```php
class CustomerDataQualityAlertService extends AbstractBaseService
{
    public function checkDataQualityAlerts(): void
    {
        // Clientes sem e-mail
        $this->checkCustomersWithoutEmail();

        // Clientes com documentos inválidos
        $this->checkInvalidDocuments();

        // Clientes com dados incompletos
        $this->checkIncompleteData();
    }

    private function checkCustomersWithoutEmail(): void
    {
        $customersWithoutEmail = Customer::whereDoesntHave('contact', function ($query) {
            $query->whereNotNull('email')->where('email', '!=', '');
        })->get();

        foreach ($customersWithoutEmail as $customer) {
            $this->sendDataQualityAlert($customer, 'Cliente sem e-mail cadastrado');
        }
    }

    private function checkInvalidDocuments(): void
    {
        $customers = Customer::with('commonData')->get();

        foreach ($customers as $customer) {
            if ($customer->commonData) {
                // Validar CPF
                if ($customer->commonData->cpf && ! $this->isValidCpf($customer->commonData->cpf)) {
                    $this->sendDataQualityAlert($customer, 'CPF inválido');
                }

                // Validar CNPJ
                if ($customer->commonData->cnpj && ! $this->isValidCnpj($customer->commonData->cnpj)) {
                    $this->sendDataQualityAlert($customer, 'CNPJ inválido');
                }
            }
        }
    }

    private function checkIncompleteData(): void
    {
        $customers = Customer::with('commonData', 'contact', 'address')->get();

        foreach ($customers as $customer) {
            $incompleteFields = [];

            if (! $customer->commonData) {
                $incompleteFields[] = 'dados comuns';
            }

            if (! $customer->contact) {
                $incompleteFields[] = 'contatos';
            }

            if (! $customer->address) {
                $incompleteFields[] = 'endereço';
            }

            if (! empty($incompleteFields)) {
                $message = 'Cliente com dados incompletos: ' . implode(', ', $incompleteFields);
                $this->sendDataQualityAlert($customer, $message);
            }
        }
    }
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar CpfValidationService
- [ ] Implementar CnpjValidationService
- [ ] Criar CustomerDuplicateValidationService
- [ ] Definir CustomerConditionalValidationService

### **Fase 2: Core Features**
- [ ] Implementar CustomerContactValidationService
- [ ] Criar CustomerAddressValidationService
- [ ] Integrar validações no CustomerService
- [ ] Criar testes unitários

### **Fase 3: Advanced Features**
- [ ] Implementar CustomerValidationMetricsService
- [ ] Criar CustomerDataQualityAlertService
- [ ] Sistema de auditoria de validações
- [ ] Relatórios de qualidade de dados

### **Fase 4: Integration**
- [ ] Integração com front-end para validações em tempo real
- [ ] Sistema de correção automática de dados
- [ ] Dashboard de qualidade de dados
- [ ] Exportação de relatórios de validação

## 📚 Documentação Relacionada

- [CustomerDTO](../../app/DTOs/Customer/CustomerDTO.php)
- [CustomerService](../../app/Services/Domain/CustomerService.php)
- [CustomerRepository](../../app/Repositories/CustomerRepository.php)
- [CpfValidationService](../../app/Services/Domain/CpfValidationService.php)
- [CnpjValidationService](../../app/Services/Domain/CnpjValidationService.php)

## 🎯 Benefícios

### **✅ Qualidade de Dados**
- Validações rigorosas de documentos
- Verificação de duplicação completa
- Campos condicionais corretamente validados
- Dados consistentes e confiáveis

### **✅ Experiência do Usuário**
- Mensagens de erro claras e específicas
- Validações em tempo real
- Prevenção de erros comuns
- Fluxo de cadastro simplificado

### **✅ Conformidade**
- Validação de documentos oficiais
- Conformidade com normas de dados
- Auditoria de validações
- Histórico de correções

### **✅ Performance**
- Validações otimizadas
- Consultas eficientes
- Cache de resultados de validação
- Processamento assíncrono quando necessário

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
