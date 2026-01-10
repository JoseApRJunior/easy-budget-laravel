# 🎯 Skill: Customer Data Quality (Qualidade de Dados)

**Descrição:** Sistema de validação, limpeza e manutenção da qualidade dos dados de clientes.

**Categoria:** Qualidade de Dados
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Garantir a qualidade, consistência e integridade dos dados de clientes no Easy Budget através de validações automatizadas, auditoria de dados e processos de limpeza.

## 📋 Requisitos Técnicos

### **✅ Sistema de Validação de Dados**

```php
class CustomerDataQualityService extends AbstractBaseService
{
    public function validateCustomerData(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $errors = [];
            $warnings = [];

            // 1. Validar dados principais
            $mainDataValidation = $this->validateMainData($customer);
            $errors = array_merge($errors, $mainDataValidation['errors']);
            $warnings = array_merge($warnings, $mainDataValidation['warnings']);

            // 2. Validar dados comuns
            if ($customer->commonData) {
                $commonDataValidation = $this->validateCommonData($customer->commonData);
                $errors = array_merge($errors, $commonDataValidation['errors']);
                $warnings = array_merge($warnings, $commonDataValidation['warnings']);
            }

            // 3. Validar contato
            if ($customer->contact) {
                $contactValidation = $this->validateContact($customer->contact);
                $errors = array_merge($errors, $contactValidation['errors']);
                $warnings = array_merge($warnings, $contactValidation['warnings']);
            }

            // 4. Validar endereço
            if ($customer->address) {
                $addressValidation = $this->validateAddress($customer->address);
                $errors = array_merge($errors, $addressValidation['errors']);
                $warnings = array_merge($warnings, $addressValidation['warnings']);
            }

            // 5. Validar dados empresariais
            if ($customer->businessData) {
                $businessDataValidation = $this->validateBusinessData($customer->businessData);
                $errors = array_merge($errors, $businessDataValidation['errors']);
                $warnings = array_merge($warnings, $businessDataValidation['warnings']);
            }

            // 6. Validar consistência entre módulos
            $consistencyValidation = $this->validateConsistency($customer);
            $errors = array_merge($errors, $consistencyValidation['errors']);
            $warnings = array_merge($warnings, $consistencyValidation['warnings']);

            $status = empty($errors) ? 'valid' : (empty($warnings) ? 'warning' : 'error');

            return $this->success([
                'status' => $status,
                'errors' => $errors,
                'warnings' => $warnings,
                'score' => $this->calculateDataQualityScore($errors, $warnings),
            ], 'Validação de dados concluída');
        });
    }

    private function validateMainData(Customer $customer): array
    {
        $errors = [];
        $warnings = [];

        // Validar status
        if (! in_array($customer->status, ['active', 'inactive', 'pending'])) {
            $errors[] = 'Status do cliente inválido';
        }

        // Validar tipo
        if (! in_array($customer->type, ['individual', 'company'])) {
            $errors[] = 'Tipo de cliente inválido';
        }

        // Validar data de criação
        if (! $customer->created_at) {
            $errors[] = 'Data de criação não informada';
        }

        // Validar último contato
        if ($customer->last_interaction_at && $customer->last_interaction_at > now()) {
            $warnings[] = 'Data do último contato no futuro';
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function validateCommonData(CommonData $commonData): array
    {
        $errors = [];
        $warnings = [];

        // Validar tipo de pessoa
        if (! in_array($commonData->type, ['individual', 'company'])) {
            $errors[] = 'Tipo de pessoa inválido nos dados comuns';
        }

        // Validar CPF/CNPJ
        if ($commonData->type === 'individual' && $commonData->cpf) {
            if (! $this->isValidCPF($commonData->cpf)) {
                $errors[] = 'CPF inválido';
            }
        } elseif ($commonData->type === 'company' && $commonData->cnpj) {
            if (! $this->isValidCNPJ($commonData->cnpj)) {
                $errors[] = 'CNPJ inválido';
            }
        }

        // Validar nome
        if (strlen($commonData->first_name) < 2) {
            $errors[] = 'Nome muito curto';
        }

        if (strlen($commonData->first_name) > 100) {
            $warnings[] = 'Nome muito longo';
        }

        // Validar data de nascimento
        if ($commonData->birth_date) {
            if ($commonData->birth_date > now()) {
                $errors[] = 'Data de nascimento no futuro';
            }

            if ($commonData->birth_date < now()->subYears(100)) {
                $warnings[] = 'Data de nascimento muito antiga';
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function validateContact(Contact $contact): array
    {
        $errors = [];
        $warnings = [];

        // Validar e-mail
        if ($contact->email && ! filter_var($contact->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido';
        }

        // Validar telefone
        if ($contact->phone && ! $this->isValidPhone($contact->phone)) {
            $warnings[] = 'Telefone no formato inválido';
        }

        // Validar telefone comercial
        if ($contact->phone_business && ! $this->isValidPhone($contact->phone_business)) {
            $warnings[] = 'Telefone comercial no formato inválido';
        }

        // Validar website
        if ($contact->website && ! filter_var($contact->website, FILTER_VALIDATE_URL)) {
            $warnings[] = 'Website inválido';
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function validateAddress(Address $address): array
    {
        $errors = [];
        $warnings = [];

        // Validar CEP
        if ($address->cep && ! $this->isValidCEP($address->cep)) {
            $errors[] = 'CEP inválido';
        }

        // Validar estado
        if ($address->state && ! $this->isValidState($address->state)) {
            $errors[] = 'Estado inválido';
        }

        // Validar cidade
        if (strlen($address->city) < 2) {
            $errors[] = 'Cidade muito curta';
        }

        // Validar bairro
        if (strlen($address->neighborhood) < 2) {
            $warnings[] = 'Bairro muito curto';
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function validateBusinessData(BusinessData $businessData): array
    {
        $errors = [];
        $warnings = [];

        // Validar inscrição estadual
        if ($businessData->state_registration && strlen($businessData->state_registration) < 2) {
            $warnings[] = 'Inscrição estadual muito curta';
        }

        // Validar inscrição municipal
        if ($businessData->municipal_registration && strlen($businessData->municipal_registration) < 2) {
            $warnings[] = 'Inscrição municipal muito curta';
        }

        // Validar data de abertura
        if ($businessData->opening_date) {
            if ($businessData->opening_date > now()) {
                $errors[] = 'Data de abertura no futuro';
            }

            if ($businessData->opening_date < now()->subYears(100)) {
                $warnings[] = 'Data de abertura muito antiga';
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function validateConsistency(Customer $customer): array
    {
        $errors = [];
        $warnings = [];

        // Verificar consistência entre tipo de cliente e dados comuns
        if ($customer->type !== $customer->commonData?->type) {
            $warnings[] = 'Inconsistência entre tipo de cliente e tipo nos dados comuns';
        }

        // Verificar se cliente PF tem CPF
        if ($customer->type === 'individual' && ! $customer->commonData?->cpf) {
            $warnings[] = 'Cliente PF sem CPF';
        }

        // Verificar se cliente PJ tem CNPJ
        if ($customer->type === 'company' && ! $customer->commonData?->cnpj) {
            $warnings[] = 'Cliente PJ sem CNPJ';
        }

        // Verificar se cliente tem contato
        if (! $customer->contact) {
            $warnings[] = 'Cliente sem informações de contato';
        }

        // Verificar se cliente tem endereço
        if (! $customer->address) {
            $warnings[] = 'Cliente sem endereço';
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function calculateDataQualityScore(array $errors, array $warnings): float
    {
        $totalIssues = count($errors) + count($warnings);
        $errorWeight = 2;
        $warningWeight = 1;

        $score = 100 - (count($errors) * $errorWeight + count($warnings) * $warningWeight);

        return max(0, min(100, $score));
    }

    private function isValidCPF(string $cpf): bool
    {
        // Implementação do algoritmo de validação de CPF
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        // Verifica se todos os dígitos são iguais
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Validação do primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        // Validação do segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        return $cpf[9] == $digit1 && $cpf[10] == $digit2;
    }

    private function isValidCNPJ(string $cnpj): bool
    {
        // Implementação do algoritmo de validação de CNPJ
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) !== 14) {
            return false;
        }

        // Verifica se todos os dígitos são iguais
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        // Validação do primeiro dígito verificador
        $sum = 0;
        $multipliers = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $multipliers[$i];
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        // Validação do segundo dígito verificador
        $sum = 0;
        $multipliers = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $multipliers[$i];
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        return $cnpj[12] == $digit1 && $cnpj[13] == $digit2;
    }

    private function isValidPhone(string $phone): bool
    {
        // Remove todos os caracteres não numéricos
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Verifica se tem entre 10 e 11 dígitos (DDD + número)
        return strlen($phone) >= 10 && strlen($phone) <= 11;
    }

    private function isValidCEP(string $cep): bool
    {
        // Remove todos os caracteres não numéricos
        $cep = preg_replace('/[^0-9]/', '', $cep);

        // Verifica se tem 8 dígitos
        return strlen($cep) === 8;
    }

    private function isValidState(string $state): bool
    {
        $validStates = [
            'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
            'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
            'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
        ];

        return in_array(strtoupper($state), $validStates);
    }
}
```

### **✅ Sistema de Auditoria de Dados**

```php
class CustomerDataAuditService extends AbstractBaseService
{
    public function auditDataQuality(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function() use ($filters) {
            $query = Customer::query();

            // Aplicar filtros
            if (isset($filters['tenant_id'])) {
                $query->where('tenant_id', $filters['tenant_id']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $customers = $query->with(['commonData', 'contact', 'address', 'businessData'])->get();

            $auditResults = [];

            foreach ($customers as $customer) {
                $validation = $this->validateCustomerData($customer);
                $auditResults[] = [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->commonData?->first_name . ' ' . $customer->commonData?->last_name,
                    'validation_result' => $validation->getData(),
                    'audit_date' => now(),
                ];
            }

            return $this->success($auditResults, 'Auditoria de qualidade de dados concluída');
        });
    }

    public function generateDataQualityReport(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function() use ($filters) {
            $auditResults = $this->auditDataQuality($filters)->getData();

            $report = [
                'total_customers' => count($auditResults),
                'valid_customers' => 0,
                'warning_customers' => 0,
                'error_customers' => 0,
                'average_score' => 0,
                'issues_summary' => [],
                'recommendations' => [],
            ];

            $totalScore = 0;
            $allErrors = [];
            $allWarnings = [];

            foreach ($auditResults as $result) {
                $validation = $result['validation_result'];
                $status = $validation['status'];
                $score = $validation['score'];

                switch ($status) {
                    case 'valid':
                        $report['valid_customers']++;
                        break;
                    case 'warning':
                        $report['warning_customers']++;
                        break;
                    case 'error':
                        $report['error_customers']++;
                        break;
                }

                $totalScore += $score;
                $allErrors = array_merge($allErrors, $validation['errors']);
                $allWarnings = array_merge($allWarnings, $validation['warnings']);
            }

            $report['average_score'] = count($auditResults) > 0 ? $totalScore / count($auditResults) : 0;

            // Sumário de issues
            $report['issues_summary'] = [
                'errors_by_type' => $this->countIssuesByType($allErrors),
                'warnings_by_type' => $this->countIssuesByType($allWarnings),
                'most_common_errors' => $this->getMostCommonIssues($allErrors, 5),
                'most_common_warnings' => $this->getMostCommonIssues($allWarnings, 5),
            ];

            // Recomendações
            $report['recommendations'] = $this->generateRecommendations($report['issues_summary']);

            return $this->success($report, 'Relatório de qualidade de dados gerado');
        });
    }

    private function countIssuesByType(array $issues): array
    {
        $counts = [];

        foreach ($issues as $issue) {
            $issueType = $this->classifyIssue($issue);
            $counts[$issueType] = ($counts[$issueType] ?? 0) + 1;
        }

        return $counts;
    }

    private function classifyIssue(string $issue): string
    {
        $issue = strtolower($issue);

        if (str_contains($issue, 'cpf') || str_contains($issue, 'cnpj')) {
            return 'document_validation';
        } elseif (str_contains($issue, 'email') || str_contains($issue, 'phone') || str_contains($issue, 'contact')) {
            return 'contact_validation';
        } elseif (str_contains($issue, 'address') || str_contains($issue, 'cep')) {
            return 'address_validation';
        } elseif (str_contains($issue, 'name') || str_contains($issue, 'date')) {
            return 'basic_validation';
        } elseif (str_contains($issue, 'consistency')) {
            return 'consistency_validation';
        } else {
            return 'other_validation';
        }
    }

    private function getMostCommonIssues(array $issues, int $limit): array
    {
        $issueCounts = array_count_values($issues);
        arsort($issueCounts);

        return array_slice($issueCounts, 0, $limit, true);
    }

    private function generateRecommendations(array $issuesSummary): array
    {
        $recommendations = [];

        // Recomendações baseadas em erros críticos
        if (isset($issuesSummary['errors_by_type']['document_validation']) && $issuesSummary['errors_by_type']['document_validation'] > 0) {
            $recommendations[] = 'Implementar validação em tempo real de CPF/CNPJ no cadastro';
        }

        if (isset($issuesSummary['errors_by_type']['contact_validation']) && $issuesSummary['errors_by_type']['contact_validation'] > 0) {
            $recommendations[] = 'Obrigar preenchimento de e-mail válido no cadastro';
        }

        // Recomendações baseadas em warnings frequentes
        if (isset($issuesSummary['warnings_by_type']['basic_validation']) && $issuesSummary['warnings_by_type']['basic_validation'] > 10) {
            $recommendations[] = 'Implementar validação de comprimento de campos no frontend';
        }

        // Recomendações baseadas em inconsistências
        if (isset($issuesSummary['errors_by_type']['consistency_validation']) && $issuesSummary['errors_by_type']['consistency_validation'] > 0) {
            $recommendations[] = 'Criar processo de correção de dados inconsistentes';
        }

        return $recommendations;
    }
}
```

### **✅ Sistema de Limpeza de Dados**

```php
class CustomerDataCleanupService extends AbstractBaseService
{
    public function cleanupDuplicateCustomers(): ServiceResult
    {
        return $this->safeExecute(function() {
            $duplicates = $this->findDuplicateCustomers();
            $cleanupResults = [];

            foreach ($duplicates as $duplicateGroup) {
                $result = $this->mergeDuplicateCustomers($duplicateGroup);
                $cleanupResults[] = $result;
            }

            return $this->success($cleanupResults, 'Limpeza de clientes duplicados concluída');
        });
    }

    public function cleanupInvalidData(): ServiceResult
    {
        return $this->safeExecute(function() {
            $cleanupResults = [];

            // Limpar CPFs inválidos
            $invalidCpfs = $this->findInvalidCPFs();
            foreach ($invalidCpfs as $customer) {
                $result = $this->fixInvalidCPF($customer);
                $cleanupResults[] = $result;
            }

            // Limpar CNPJs inválidos
            $invalidCnpjs = $this->findInvalidCNPJs();
            foreach ($invalidCnpjs as $customer) {
                $result = $this->fixInvalidCNPJ($customer);
                $cleanupResults[] = $result;
            }

            // Limpar e-mails inválidos
            $invalidEmails = $this->findInvalidEmails();
            foreach ($invalidEmails as $customer) {
                $result = $this->fixInvalidEmail($customer);
                $cleanupResults[] = $result;
            }

            return $this->success($cleanupResults, 'Limpeza de dados inválidos concluída');
        });
    }

    public function standardizeDataFormat(): ServiceResult
    {
        return $this->safeExecute(function() {
            $standardizationResults = [];

            // Padronizar formatos de telefone
            $phoneResults = $this->standardizePhoneFormats();
            $standardizationResults = array_merge($standardizationResults, $phoneResults);

            // Padronizar formatos de CEP
            $cepResults = $this->standardizeCEPFormats();
            $standardizationResults = array_merge($standardizationResults, $cepResults);

            // Padronizar formatos de CPF/CNPJ
            $documentResults = $this->standardizeDocumentFormats();
            $standardizationResults = array_merge($standardizationResults, $documentResults);

            return $this->success($standardizationResults, 'Padronização de formatos concluída');
        });
    }

    private function findDuplicateCustomers(): array
    {
        // Encontrar clientes com mesmo CPF/CNPJ
        $duplicateDocs = Customer::whereHas('commonData', function($query) {
            $query->whereNotNull('cpf')->orWhereNotNull('cnpj');
        })
        ->with('commonData')
        ->get()
        ->groupBy(function($customer) {
            return $customer->commonData->cpf ?? $customer->commonData->cnpj;
        })
        ->filter(function($group) {
            return $group->count() > 1;
        })
        ->values()
        ->toArray();

        // Encontrar clientes com mesmo e-mail
        $duplicateEmails = Customer::whereHas('contact', function($query) {
            $query->whereNotNull('email');
        })
        ->with('contact')
        ->get()
        ->groupBy(function($customer) {
            return $customer->contact->email;
        })
        ->filter(function($group) {
            return $group->count() > 1;
        })
        ->values()
        ->toArray();

        return array_merge($duplicateDocs, $duplicateEmails);
    }

    private function mergeDuplicateCustomers(array $duplicateGroup): array
    {
        // Ordenar por data de criação (manter o mais antigo)
        usort($duplicateGroup, function($a, $b) {
            return $a->created_at <=> $b->created_at;
        });

        $mainCustomer = $duplicateGroup[0];
        $duplicates = array_slice($duplicateGroup, 1);

        $mergedData = [
            'customer_id' => $mainCustomer->id,
            'customer_name' => $mainCustomer->commonData?->first_name,
            'duplicates_merged' => count($duplicates),
            'actions' => [],
        ];

        foreach ($duplicates as $duplicate) {
            // Mover relacionamentos para o cliente principal
            $this->moveRelationships($duplicate, $mainCustomer);

            // Excluir cliente duplicado
            $duplicate->delete();

            $mergedData['actions'][] = "Cliente duplicado {$duplicate->id} movido para {$mainCustomer->id}";
        }

        return $mergedData;
    }

    private function moveRelationships(Customer $fromCustomer, Customer $toCustomer): void
    {
        // Mover orçamentos
        $fromCustomer->budgets()->update(['customer_id' => $toCustomer->id]);

        // Mover interações
        $fromCustomer->interactions()->update(['customer_id' => $toCustomer->id]);

        // Mover histórico de ciclo de vida
        $fromCustomer->lifecycleHistory()->update(['customer_id' => $toCustomer->id]);

        // Mover tags
        foreach ($fromCustomer->tags as $tag) {
            if (! $toCustomer->tags->contains($tag)) {
                $toCustomer->tags()->attach($tag);
            }
        }
    }

    private function findInvalidCPFs(): Collection
    {
        return Customer::whereHas('commonData', function($query) {
            $query->whereNotNull('cpf')
                ->where('type', 'individual');
        })
        ->with('commonData')
        ->get()
        ->filter(function($customer) {
            return ! $this->isValidCPF($customer->commonData->cpf);
        });
    }

    private function findInvalidCNPJs(): Collection
    {
        return Customer::whereHas('commonData', function($query) {
            $query->whereNotNull('cnpj')
                ->where('type', 'company');
        })
        ->with('commonData')
        ->get()
        ->filter(function($customer) {
            return ! $this->isValidCNPJ($customer->commonData->cnpj);
        });
    }

    private function findInvalidEmails(): Collection
    {
        return Customer::whereHas('contact', function($query) {
            $query->whereNotNull('email');
        })
        ->with('contact')
        ->get()
        ->filter(function($customer) {
            return ! filter_var($customer->contact->email, FILTER_VALIDATE_EMAIL);
        });
    }

    private function fixInvalidCPF(Customer $customer): array
    {
        $originalCpf = $customer->commonData->cpf;
        $customer->commonData->cpf = null;
        $customer->commonData->save();

        return [
            'customer_id' => $customer->id,
            'action' => 'removed_invalid_cpf',
            'original_value' => $originalCpf,
            'new_value' => null,
        ];
    }

    private function fixInvalidCNPJ(Customer $customer): array
    {
        $originalCnpj = $customer->commonData->cnpj;
        $customer->commonData->cnpj = null;
        $customer->commonData->save();

        return [
            'customer_id' => $customer->id,
            'action' => 'removed_invalid_cnpj',
            'original_value' => $originalCnpj,
            'new_value' => null,
        ];
    }

    private function fixInvalidEmail(Customer $customer): array
    {
        $originalEmail = $customer->contact->email;
        $customer->contact->email = null;
        $customer->contact->save();

        return [
            'customer_id' => $customer->id,
            'action' => 'removed_invalid_email',
            'original_value' => $originalEmail,
            'new_value' => null,
        ];
    }

    private function standardizePhoneFormats(): array
    {
        $results = [];

        $customers = Customer::whereHas('contact', function($query) {
            $query->whereNotNull('phone');
        })->with('contact')->get();

        foreach ($customers as $customer) {
            $originalPhone = $customer->contact->phone;
            $standardizedPhone = $this->standardizePhone($originalPhone);

            if ($originalPhone !== $standardizedPhone) {
                $customer->contact->phone = $standardizedPhone;
                $customer->contact->save();

                $results[] = [
                    'customer_id' => $customer->id,
                    'field' => 'phone',
                    'original' => $originalPhone,
                    'standardized' => $standardizedPhone,
                ];
            }
        }

        return $results;
    }

    private function standardizeCEPFormats(): array
    {
        $results = [];

        $customers = Customer::whereHas('address', function($query) {
            $query->whereNotNull('cep');
        })->with('address')->get();

        foreach ($customers as $customer) {
            $originalCep = $customer->address->cep;
            $standardizedCep = $this->standardizeCEP($originalCep);

            if ($originalCep !== $standardizedCep) {
                $customer->address->cep = $standardizedCep;
                $customer->address->save();

                $results[] = [
                    'customer_id' => $customer->id,
                    'field' => 'cep',
                    'original' => $originalCep,
                    'standardized' => $standardizedCep,
                ];
            }
        }

        return $results;
    }

    private function standardizeDocumentFormats(): array
    {
        $results = [];

        // Padronizar CPFs
        $customers = Customer::whereHas('commonData', function($query) {
            $query->whereNotNull('cpf');
        })->with('commonData')->get();

        foreach ($customers as $customer) {
            $originalCpf = $customer->commonData->cpf;
            $standardizedCpf = $this->standardizeCPF($originalCpf);

            if ($originalCpf !== $standardizedCpf) {
                $customer->commonData->cpf = $standardizedCpf;
                $customer->commonData->save();

                $results[] = [
                    'customer_id' => $customer->id,
                    'field' => 'cpf',
                    'original' => $originalCpf,
                    'standardized' => $standardizedCpf,
                ];
            }
        }

        // Padronizar CNPJs
        $customers = Customer::whereHas('commonData', function($query) {
            $query->whereNotNull('cnpj');
        })->with('commonData')->get();

        foreach ($customers as $customer) {
            $originalCnpj = $customer->commonData->cnpj;
            $standardizedCnpj = $this->standardizeCNPJ($originalCnpj);

            if ($originalCnpj !== $standardizedCnpj) {
                $customer->commonData->cnpj = $standardizedCnpj;
                $customer->commonData->save();

                $results[] = [
                    'customer_id' => $customer->id,
                    'field' => 'cnpj',
                    'original' => $originalCnpj,
                    'standardized' => $standardizedCnpj,
                ];
            }
        }

        return $results;
    }

    private function standardizePhone(string $phone): string
    {
        // Remove todos os caracteres não numéricos
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        // Formato: (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
        if (strlen($cleanPhone) === 11) {
            return '(' . substr($cleanPhone, 0, 2) . ') ' . substr($cleanPhone, 2, 5) . '-' . substr($cleanPhone, 7, 4);
        } elseif (strlen($cleanPhone) === 10) {
            return '(' . substr($cleanPhone, 0, 2) . ') ' . substr($cleanPhone, 2, 4) . '-' . substr($cleanPhone, 6, 4);
        }

        return $phone; // Retorna original se não puder padronizar
    }

    private function standardizeCEP(string $cep): string
    {
        // Remove todos os caracteres não numéricos
        $cleanCep = preg_replace('/[^0-9]/', '', $cep);

        // Formato: XXXXX-XXX
        if (strlen($cleanCep) === 8) {
            return substr($cleanCep, 0, 5) . '-' . substr($cleanCep, 5, 3);
        }

        return $cep; // Retorna original se não puder padronizar
    }

    private function standardizeCPF(string $cpf): string
    {
        // Remove todos os caracteres não numéricos
        $cleanCpf = preg_replace('/[^0-9]/', '', $cpf);

        // Formato: XXX.XXX.XXX-XX
        if (strlen($cleanCpf) === 11) {
            return substr($cleanCpf, 0, 3) . '.' . substr($cleanCpf, 3, 3) . '.' . substr($cleanCpf, 6, 3) . '-' . substr($cleanCpf, 9, 2);
        }

        return $cpf; // Retorna original se não puder padronizar
    }

    private function standardizeCNPJ(string $cnpj): string
    {
        // Remove todos os caracteres não numéricos
        $cleanCnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Formato: XX.XXX.XXX/XXXX-XX
        if (strlen($cleanCnpj) === 14) {
            return substr($cleanCnpj, 0, 2) . '.' . substr($cleanCnpj, 2, 3) . '.' . substr($cleanCnpj, 5, 3) . '/' . substr($cleanCnpj, 8, 4) . '-' . substr($cleanCnpj, 12, 2);
        }

        return $cnpj; // Retorna original se não puder padronizar
    }
}
```

## 📊 Métricas de Qualidade de Dados

### **✅ Sistema de Métricas**

```php
class CustomerDataQualityMetricsService extends AbstractBaseService
{
    public function getDataQualityMetrics(array $filters = []): array
    {
        $query = Customer::query();

        // Aplicar filtros
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        $customers = $query->with(['commonData', 'contact', 'address', 'businessData'])->get();

        return [
            'total_customers' => $customers->count(),
            'completeness_metrics' => $this->getCompletenessMetrics($customers),
            'accuracy_metrics' => $this->getAccuracyMetrics($customers),
            'consistency_metrics' => $this->getConsistencyMetrics($customers),
            'timeliness_metrics' => $this->getTimelinessMetrics($customers),
            'uniqueness_metrics' => $this->getUniquenessMetrics($customers),
            'overall_score' => $this->calculateOverallScore($customers),
        ];
    }

    private function getCompletenessMetrics(Collection $customers): array
    {
        $totalCustomers = $customers->count();

        $metrics = [
            'common_data_completeness' => $this->calculateCompleteness($customers, function($customer) {
                return $customer->commonData && $customer->commonData->first_name && $customer->commonData->last_name;
            }),
            'contact_completeness' => $this->calculateCompleteness($customers, function($customer) {
                return $customer->contact && $customer->contact->email;
            }),
            'address_completeness' => $this->calculateCompleteness($customers, function($customer) {
                return $customer->address && $customer->address->address && $customer->address->city && $customer->address->state;
            }),
            'business_data_completeness' => $this->calculateCompleteness($customers, function($customer) {
                return $customer->businessData && $customer->businessData->opening_date;
            }),
        ];

        $metrics['overall_completeness'] = array_sum($metrics) / count($metrics);

        return $metrics;
    }

    private function getAccuracyMetrics(Collection $customers): array
    {
        $totalCustomers = $customers->count();

        $metrics = [
            'email_accuracy' => $this->calculateAccuracy($customers, function($customer) {
                return $customer->contact && filter_var($customer->contact->email, FILTER_VALIDATE_EMAIL);
            }),
            'phone_accuracy' => $this->calculateAccuracy($customers, function($customer) {
                return $customer->contact && $this->isValidPhone($customer->contact->phone);
            }),
            'document_accuracy' => $this->calculateAccuracy($customers, function($customer) {
                if ($customer->commonData->type === 'individual') {
                    return $customer->commonData->cpf && $this->isValidCPF($customer->commonData->cpf);
                } else {
                    return $customer->commonData->cnpj && $this->isValidCNPJ($customer->commonData->cnpj);
                }
            }),
            'address_accuracy' => $this->calculateAccuracy($customers, function($customer) {
                return $customer->address && $this->isValidCEP($customer->address->cep);
            }),
        ];

        $metrics['overall_accuracy'] = array_sum($metrics) / count($metrics);

        return $metrics;
    }

    private function getConsistencyMetrics(Collection $customers): array
    {
        $totalCustomers = $customers->count();

        $metrics = [
            'type_consistency' => $this->calculateConsistency($customers, function($customer) {
                return $customer->type === $customer->commonData?->type;
            }),
            'document_consistency' => $this->calculateConsistency($customers, function($customer) {
                if ($customer->type === 'individual') {
                    return $customer->commonData?->cpf !== null;
                } else {
                    return $customer->commonData?->cnpj !== null;
                }
            }),
            'contact_consistency' => $this->calculateConsistency($customers, function($customer) {
                return $customer->contact !== null;
            }),
            'address_consistency' => $this->calculateConsistency($customers, function($customer) {
                return $customer->address !== null;
            }),
        ];

        $metrics['overall_consistency'] = array_sum($metrics) / count($metrics);

        return $metrics;
    }

    private function getTimelinessMetrics(Collection $customers): array
    {
        $totalCustomers = $customers->count();

        $metrics = [
            'recent_interactions' => $this->calculateTimeliness($customers, function($customer) {
                return $customer->last_interaction_at && $customer->last_interaction_at >= now()->subMonths(3);
            }),
            'recent_budgets' => $this->calculateTimeliness($customers, function($customer) {
                return $customer->last_budget_at && $customer->last_budget_at >= now()->subMonths(6);
            }),
            'recent_services' => $this->calculateTimeliness($customers, function($customer) {
                return $customer->last_service_at && $customer->last_service_at >= now()->subMonths(6);
            }),
            'recent_invoices' => $this->calculateTimeliness($customers, function($customer) {
                return $customer->last_invoice_at && $customer->last_invoice_at >= now()->subMonths(6);
            }),
        ];

        $metrics['overall_timeliness'] = array_sum($metrics) / count($metrics);

        return $metrics;
    }

    private function getUniquenessMetrics(Collection $customers): array
    {
        $totalCustomers = $customers->count();

        // Verificar duplicatas por CPF/CNPJ
        $duplicateDocs = $customers->groupBy(function($customer) {
            return $customer->commonData?->cpf ?? $customer->commonData?->cnpj;
        })->filter(function($group) {
            return $group->count() > 1;
        });

        // Verificar duplicatas por e-mail
        $duplicateEmails = $customers->groupBy(function($customer) {
            return $customer->contact?->email;
        })->filter(function($group) {
            return $group->count() > 1;
        });

        $uniqueCustomers = $totalCustomers - $duplicateDocs->sum->count() - $duplicateEmails->sum->count();

        return [
            'unique_customers' => $uniqueCustomers,
            'duplicate_customers' => $duplicateDocs->sum->count() + $duplicateEmails->sum->count(),
            'uniqueness_ratio' => $totalCustomers > 0 ? ($uniqueCustomers / $totalCustomers) * 100 : 0,
            'duplicate_by_document' => $duplicateDocs->count(),
            'duplicate_by_email' => $duplicateEmails->count(),
        ];
    }

    private function calculateOverallScore(Collection $customers): float
    {
        $metrics = $this->getDataQualityMetrics();

        $completeness = $metrics['completeness_metrics']['overall_completeness'] ?? 0;
        $accuracy = $metrics['accuracy_metrics']['overall_accuracy'] ?? 0;
        $consistency = $metrics['consistency_metrics']['overall_consistency'] ?? 0;
        $timeliness = $metrics['timeliness_metrics']['overall_timeliness'] ?? 0;
        $uniqueness = $metrics['uniqueness_metrics']['uniqueness_ratio'] ?? 0;

        return ($completeness + $accuracy + $consistency + $timeliness + $uniqueness) / 5;
    }

    private function calculateCompleteness(Collection $customers, callable $condition): float
    {
        $totalCustomers = $customers->count();
        $completeCustomers = $customers->filter($condition)->count();

        return $totalCustomers > 0 ? ($completeCustomers / $totalCustomers) * 100 : 0;
    }

    private function calculateAccuracy(Collection $customers, callable $condition): float
    {
        $totalCustomers = $customers->count();
        $accurateCustomers = $customers->filter($condition)->count();

        return $totalCustomers > 0 ? ($accurateCustomers / $totalCustomers) * 100 : 0;
    }

    private function calculateConsistency(Collection $customers, callable $condition): float
    {
        $totalCustomers = $customers->count();
        $consistentCustomers = $customers->filter($condition)->count();

        return $totalCustomers > 0 ? ($consistentCustomers / $totalCustomers) * 100 : 0;
    }

    private function calculateTimeliness(Collection $customers, callable $condition): float
    {
        $totalCustomers = $customers->count();
        $timelyCustomers = $customers->filter($condition)->count();

        return $totalCustomers > 0 ? ($timelyCustomers / $totalCustomers) * 100 : 0;
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Qualidade de Dados**

```php
public function testCustomerDataValidation()
{
    $customer = Customer::factory()->create();
    $customer->commonData()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'type' => 'individual',
        'cpf' => '12345678909', // CPF inválido
    ]);
    $customer->contact()->create([
        'email' => 'invalid-email', // E-mail inválido
    ]);
    $customer->address()->create([
        'address' => 'Test Address',
        'city' => 'Test City',
        'state' => 'XX', // Estado inválido
        'cep' => '123456789', // CEP inválido
    ]);

    $result = $this->dataQualityService->validateCustomerData($customer);
    $this->assertTrue($result->isSuccess());

    $validationData = $result->getData();
    $this->assertEquals('error', $validationData['status']);
    $this->assertGreaterThan(0, count($validationData['errors']));
    $this->assertGreaterThan(0, count($validationData['warnings']));
    $this->assertLessThan(100, $validationData['score']);
}

public function testValidCPFValidation()
{
    $this->assertTrue($this->dataQualityService->isValidCPF('11144477735'));
    $this->assertFalse($this->dataQualityService->isValidCPF('12345678909'));
    $this->assertFalse($this->dataQualityService->isValidCPF('11111111111'));
}

public function testValidCNPJValidation()
{
    $this->assertTrue($this->dataQualityService->isValidCNPJ('11444777000161'));
    $this->assertFalse($this->dataQualityService->isValidCNPJ('12345678000195'));
    $this->assertFalse($this->dataQualityService->isValidCNPJ('11111111111111'));
}

public function testDataQualityAudit()
{
    $tenant = Tenant::factory()->create();

    // Criar clientes com diferentes níveis de qualidade
    Customer::factory()->count(5)->create(['tenant_id' => $tenant->id]);
    Customer::factory()->count(3)->create(['tenant_id' => $tenant->id, 'status' => 'invalid']);

    $result = $this->auditService->auditDataQuality(['tenant_id' => $tenant->id]);
    $this->assertTrue($result->isSuccess());

    $auditResults = $result->getData();
    $this->assertCount(8, $auditResults);
}

public function testDataQualityReport()
{
    $tenant = Tenant::factory()->create();

    $result = $this->auditService->generateDataQualityReport(['tenant_id' => $tenant->id]);
    $this->assertTrue($result->isSuccess());

    $report = $result->getData();
    $this->assertArrayHasKey('total_customers', $report);
    $this->assertArrayHasKey('valid_customers', $report);
    $this->assertArrayHasKey('issues_summary', $report);
    $this->assertArrayHasKey('recommendations', $report);
}

public function testDataCleanup()
{
    // Criar cliente com CPF inválido
    $customer = Customer::factory()->create();
    $customer->commonData()->create([
        'cpf' => '12345678909', // CPF inválido
    ]);

    $result = $this->cleanupService->cleanupInvalidData();
    $this->assertTrue($result->isSuccess());

    $this->assertNull($customer->commonData->fresh()->cpf);
}

public function testDataStandardization()
{
    $customer = Customer::factory()->create();
    $customer->contact()->create([
        'phone' => '11987654321',
    ]);
    $customer->address()->create([
        'cep' => '01234567',
    ]);

    $result = $this->cleanupService->standardizeDataFormat();
    $this->assertTrue($result->isSuccess());

    $this->assertEquals('(11) 98765-4321', $customer->contact->fresh()->phone);
    $this->assertEquals('01234-567', $customer->address->fresh()->cep);
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar CustomerDataQualityService básico
- [ ] Criar validações de CPF/CNPJ
- [ ] Implementar validações de e-mail e telefone
- [ ] Criar sistema de auditoria básico

### **Fase 2: Core Features**
- [ ] Implementar CustomerDataAuditService
- [ ] Criar CustomerDataCleanupService
- [ ] Sistema de padronização de formatos
- [ ] Métricas de qualidade de dados

### **Fase 3: Advanced Features**
- [ ] Sistema de alertas de qualidade
- [ ] Dashboard de qualidade de dados
- [ ] Processos de correção automática
- [ ] Integração com validação em tempo real

### **Fase 4: Integration**
- [ ] Integração com sistemas externos de validação
- [ ] API para validação de dados
- [ ] Sistema de machine learning para detecção de anomalias
- [ ] Relatórios avançados de qualidade

## 📚 Documentação Relacionada

- [CustomerDataQualityService](../../app/Services/Domain/CustomerDataQualityService.php)
- [CustomerDataAuditService](../../app/Services/Domain/CustomerDataAuditService.php)
- [CustomerDataCleanupService](../../app/Services/Domain/CustomerDataCleanupService.php)
- [CustomerDataQualityMetricsService](../../app/Services/Domain/CustomerDataQualityMetricsService.php)
- [Validação de CPF/CNPJ](../../app/Support/clean_document_partial.php)

## 🎯 Benefícios

### **✅ Qualidade de Dados**
- Dados consistentes e precisos
- Redução de erros de digitação
- Padronização de formatos
- Eliminação de duplicatas

### **✅ Conformidade**
- Dados válidos para obrigações fiscais
- Conformidade com LGPD
- Auditoria completa de alterações
- Rastreabilidade de correções

### **✅ Performance**
- Consultas mais rápidas com dados limpos
- Redução de storage com duplicatas
- Melhor indexação e busca
- Processamento mais eficiente

### **✅ Decisão de Negócio**
- Relatórios confiáveis
- Métricas precisas
- Análise de dados consistente
- Insights baseados em dados de qualidade

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
