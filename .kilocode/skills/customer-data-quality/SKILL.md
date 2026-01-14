# 🧹 Skill: Customer Data Quality (Qualidade de Dados)

**Descrição:** Sistema de validação, auditoria e correção de dados de clientes, garantindo alta qualidade e consistência dos dados ao longo do tempo.

**Categoria:** Qualidade de Dados e Auditoria
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Garantir a qualidade, consistência e integridade dos dados de clientes através de validações automatizadas, auditoria contínua e processos de correção de dados.

## 📋 Requisitos Técnicos

### **✅ Sistema de Validação de Dados**

```php
class CustomerDataQualityService extends AbstractBaseService
{
    public function validateCustomerData(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $validationResults = [];

            // 1. Validar dados obrigatórios
            $mandatoryValidation = $this->validateMandatoryFields($customer);
            $validationResults['mandatory_fields'] = $mandatoryValidation;

            // 2. Validar formatos de dados
            $formatValidation = $this->validateDataFormats($customer);
            $validationResults['data_formats'] = $formatValidation;

            // 3. Validar consistência de dados
            $consistencyValidation = $this->validateDataConsistency($customer);
            $validationResults['data_consistency'] = $consistencyValidation;

            // 4. Validar duplicidades
            $duplicateValidation = $this->validateDuplicates($customer);
            $validationResults['duplicates'] = $duplicateValidation;

            // 5. Validar integridade de relacionamentos
            $relationshipValidation = $this->validateRelationships($customer);
            $validationResults['relationships'] = $relationshipValidation;

            // 6. Calcular score de qualidade
            $qualityScore = $this->calculateQualityScore($validationResults);

            $isValid = collect($validationResults)->every(fn($result) => $result['valid']);

            return $this->success([
                'valid' => $isValid,
                'quality_score' => $qualityScore,
                'validation_results' => $validationResults,
                'issues' => $this->collectIssues($validationResults),
                'recommendations' => $this->generateRecommendations($validationResults),
            ], $isValid ? 'Dados do cliente válidos' : 'Dados do cliente com problemas');
        });
    }

    public function auditCustomerData(int $tenantId, array $filters = []): ServiceResult
    {
        return $this->safeExecute(function() use ($tenantId, $filters) {
            $auditResults = [];

            // 1. Auditoria de completude de dados
            $completenessAudit = $this->auditDataCompleteness($tenantId, $filters);
            $auditResults['completeness'] = $completenessAudit;

            // 2. Auditoria de consistência de dados
            $consistencyAudit = $this->auditDataConsistency($tenantId, $filters);
            $auditResults['consistency'] = $consistencyAudit;

            // 3. Auditoria de duplicidades
            $duplicateAudit = $this->auditDuplicates($tenantId, $filters);
            $auditResults['duplicates'] = $duplicateAudit;

            // 4. Auditoria de formatos de dados
            $formatAudit = $this->auditDataFormats($tenantId, $filters);
            $auditResults['formats'] = $formatAudit;

            // 5. Auditoria de relacionamentos
            $relationshipAudit = $this->auditRelationships($tenantId, $filters);
            $auditResults['relationships'] = $relationshipAudit;

            // 6. Gerar relatório de qualidade geral
            $overallReport = $this->generateOverallQualityReport($auditResults);

            return $this->success([
                'audit_results' => $auditResults,
                'overall_report' => $overallReport,
                'summary' => $this->generateAuditSummary($auditResults),
            ], 'Auditoria de qualidade de dados concluída');
        });
    }

    public function correctCustomerData(Customer $customer, array $corrections): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $corrections) {
            $correctionResults = [];

            foreach ($corrections as $field => $correction) {
                $result = $this->applyCorrection($customer, $field, $correction);
                $correctionResults[$field] = $result;
            }

            // Atualizar score de qualidade após correções
            $validationResult = $this->validateCustomerData($customer);
            $finalQualityScore = $validationResult->getData()['quality_score'];

            return $this->success([
                'correction_results' => $correctionResults,
                'final_quality_score' => $finalQualityScore,
                'validation_after_correction' => $validationResult->getData(),
            ], 'Correções de dados aplicadas');
        });
    }

    public function standardizeCustomerData(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $standardizations = [];

            // 1. Padronizar nomes
            $nameStandardization = $this->standardizeNames($customer);
            $standardizations['names'] = $nameStandardization;

            // 2. Padronizar endereços
            $addressStandardization = $this->standardizeAddresses($customer);
            $standardizations['addresses'] = $addressStandardization;

            // 3. Padronizar contatos
            $contactStandardization = $this->standardizeContacts($customer);
            $standardizations['contacts'] = $contactStandardization;

            // 4. Padronizar documentos
            $documentStandardization = $this->standardizeDocuments($customer);
            $standardizations['documents'] = $documentStandardization;

            // 5. Padronizar formatos de data
            $dateStandardization = $this->standardizeDates($customer);
            $standardizations['dates'] = $dateStandardization;

            return $this->success([
                'standardizations' => $standardizations,
                'customer_updated' => $customer->fresh(),
            ], 'Padronização de dados concluída');
        });
    }

    private function validateMandatoryFields(Customer $customer): array
    {
        $issues = [];
        $valid = true;

        // Validar campos obrigatórios baseados no tipo de cliente
        if ($customer->type === 'individual') {
            if (empty($customer->commonData?->first_name)) {
                $issues[] = 'Nome do cliente PF é obrigatório';
                $valid = false;
            }

            if (empty($customer->commonData?->cpf)) {
                $issues[] = 'CPF é obrigatório para clientes PF';
                $valid = false;
            }
        } elseif ($customer->type === 'company') {
            if (empty($customer->commonData?->company_name)) {
                $issues[] = 'Razão social é obrigatória para clientes PJ';
                $valid = false;
            }

            if (empty($customer->commonData?->cnpj)) {
                $issues[] = 'CNPJ é obrigatório para clientes PJ';
                $valid = false;
            }
        }

        // Validar endereço obrigatório
        if (empty($customer->address?->address) || empty($customer->address?->city)) {
            $issues[] = 'Endereço completo é obrigatório';
            $valid = false;
        }

        // Validar contato obrigatório
        if (empty($customer->contact?->email) && empty($customer->contact?->phone)) {
            $issues[] = 'É necessário pelo menos um meio de contato (e-mail ou telefone)';
            $valid = false;
        }

        return [
            'valid' => $valid,
            'issues' => $issues,
            'weight' => 40, // Peso alto para campos obrigatórios
        ];
    }

    private function validateDataFormats(Customer $customer): array
    {
        $issues = [];
        $valid = true;

        // Validar formato de e-mail
        if ($customer->contact?->email && !filter_var($customer->contact->email, FILTER_VALIDATE_EMAIL)) {
            $issues[] = 'Formato de e-mail inválido';
            $valid = false;
        }

        // Validar formato de telefone
        if ($customer->contact?->phone && !preg_match('/^\(\d{2}\)\s\d{4,5}-\d{4}$/', $customer->contact->phone)) {
            $issues[] = 'Formato de telefone inválido (use (XX) XXXXX-XXXX)';
            $valid = false;
        }

        // Validar formato de CEP
        if ($customer->address?->cep && !preg_match('/^\d{5}-\d{3}$/', $customer->address->cep)) {
            $issues[] = 'Formato de CEP inválido (use XXXXX-XXX)';
            $valid = false;
        }

        // Validar formato de CPF
        if ($customer->commonData?->cpf && !$this->isValidCPF($customer->commonData->cpf)) {
            $issues[] = 'CPF inválido';
            $valid = false;
        }

        // Validar formato de CNPJ
        if ($customer->commonData?->cnpj && !$this->isValidCNPJ($customer->commonData->cnpj)) {
            $issues[] = 'CNPJ inválido';
            $valid = false;
        }

        return [
            'valid' => $valid,
            'issues' => $issues,
            'weight' => 30,
        ];
    }

    private function validateDataConsistency(Customer $customer): array
    {
        $issues = [];
        $valid = true;

        // Validar consistência entre tipo de cliente e documentos
        if ($customer->type === 'individual' && $customer->commonData?->cnpj) {
            $issues[] = 'Cliente PF não deve ter CNPJ';
            $valid = false;
        }

        if ($customer->type === 'company' && $customer->commonData?->cpf) {
            $issues[] = 'Cliente PJ não deve ter CPF';
            $valid = false;
        }

        // Validar consistência de endereço
        if ($customer->address?->state && !in_array($customer->address->state, $this->getValidStates())) {
            $issues[] = 'Estado inválido';
            $valid = false;
        }

        // Validar consistência de status
        if ($customer->status === 'active' && $customer->deactivated_at) {
            $issues[] = 'Cliente ativo não deve ter data de desativação';
            $valid = false;
        }

        if ($customer->status === 'inactive' && !$customer->deactivated_at) {
            $issues[] = 'Cliente inativo deve ter data de desativação';
            $valid = false;
        }

        return [
            'valid' => $valid,
            'issues' => $issues,
            'weight' => 20,
        ];
    }

    private function validateDuplicates(Customer $customer): array
    {
        $issues = [];
        $valid = true;

        // Verificar duplicidade de CPF/CNPJ
        if ($customer->type === 'individual' && $customer->commonData?->cpf) {
            $duplicate = Customer::where('tenant_id', $customer->tenant_id)
                ->whereHas('commonData', function($query) use ($customer) {
                    $query->where('cpf', $customer->commonData->cpf)
                          ->where('id', '!=', $customer->commonData->id);
                })
                ->exists();

            if ($duplicate) {
                $issues[] = 'Já existe cliente com este CPF';
                $valid = false;
            }
        }

        if ($customer->type === 'company' && $customer->commonData?->cnpj) {
            $duplicate = Customer::where('tenant_id', $customer->tenant_id)
                ->whereHas('commonData', function($query) use ($customer) {
                    $query->where('cnpj', $customer->commonData->cnpj)
                          ->where('id', '!=', $customer->commonData->id);
                })
                ->exists();

            if ($duplicate) {
                $issues[] = 'Já existe cliente com este CNPJ';
                $valid = false;
            }
        }

        // Verificar duplicidade de e-mail
        if ($customer->contact?->email) {
            $duplicate = Customer::where('tenant_id', $customer->tenant_id)
                ->whereHas('contact', function($query) use ($customer) {
                    $query->where('email', $customer->contact->email)
                          ->where('id', '!=', $customer->contact->id);
                })
                ->exists();

            if ($duplicate) {
                $issues[] = 'Já existe cliente com este e-mail';
                $valid = false;
            }
        }

        return [
            'valid' => $valid,
            'issues' => $issues,
            'weight' => 30,
        ];
    }

    private function validateRelationships(Customer $customer): array
    {
        $issues = [];
        $valid = true;

        // Validar integridade dos relacionamentos
        if (!$customer->commonData) {
            $issues[] = 'Cliente deve ter dados comuns associados';
            $valid = false;
        }

        if (!$customer->contact) {
            $issues[] = 'Cliente deve ter contato associado';
            $valid = false;
        }

        if (!$customer->address) {
            $issues[] = 'Cliente deve ter endereço associado';
            $valid = false;
        }

        // Validar relacionamento com tenant
        if (!$customer->tenant) {
            $issues[] = 'Cliente deve pertencer a um tenant válido';
            $valid = false;
        }

        return [
            'valid' => $valid,
            'issues' => $issues,
            'weight' => 25,
        ];
    }

    private function calculateQualityScore(array $validationResults): float
    {
        $totalWeight = 0;
        $totalScore = 0;

        foreach ($validationResults as $result) {
            $weight = $result['weight'] ?? 10;
            $score = $result['valid'] ? 100 : 0;

            $totalWeight += $weight;
            $totalScore += $score * $weight;
        }

        return $totalWeight > 0 ? ($totalScore / $totalWeight) : 100;
    }

    private function collectIssues(array $validationResults): array
    {
        $allIssues = [];

        foreach ($validationResults as $category => $result) {
            if (isset($result['issues']) && !empty($result['issues'])) {
                $allIssues[$category] = $result['issues'];
            }
        }

        return $allIssues;
    }

    private function generateRecommendations(array $validationResults): array
    {
        $recommendations = [];

        foreach ($validationResults as $category => $result) {
            if (!$result['valid'] && isset($result['issues'])) {
                foreach ($result['issues'] as $issue) {
                    $recommendations[] = [
                        'category' => $category,
                        'issue' => $issue,
                        'priority' => $this->getIssuePriority($category),
                        'action' => $this->getRecommendedAction($category, $issue),
                    ];
                }
            }
        }

        return $recommendations;
    }

    private function auditDataCompleteness(int $tenantId, array $filters): array
    {
        $totalCustomers = Customer::where('tenant_id', $tenantId)
            ->when($filters['status'] ?? null, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['type'] ?? null, function($query, $type) {
                $query->where('type', $type);
            })
            ->count();

        $completeCustomers = Customer::where('tenant_id', $tenantId)
            ->whereHas('commonData')
            ->whereHas('contact')
            ->whereHas('address')
            ->when($filters['status'] ?? null, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['type'] ?? null, function($query, $type) {
                $query->where('type', $type);
            })
            ->count();

        return [
            'total_customers' => $totalCustomers,
            'complete_customers' => $completeCustomers,
            'completeness_rate' => $totalCustomers > 0 ? ($completeCustomers / $totalCustomers) * 100 : 0,
            'incomplete_customers' => $totalCustomers - $completeCustomers,
        ];
    }

    private function auditDataConsistency(int $tenantId, array $filters): array
    {
        $inconsistentCustomers = Customer::where('tenant_id', $tenantId)
            ->where(function($query) {
                // Clientes PF com CNPJ
                $query->where('type', 'individual')
                      ->whereHas('commonData', function($subquery) {
                          $subquery->whereNotNull('cnpj');
                      })
                      ->orWhere(function($subquery) {
                          // Clientes PJ com CPF
                          $subquery->where('type', 'company')
                                   ->whereHas('commonData', function($innerQuery) {
                                       $innerQuery->whereNotNull('cpf');
                                   });
                      });
            })
            ->when($filters['status'] ?? null, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['type'] ?? null, function($query, $type) {
                $query->where('type', $type);
            })
            ->count();

        $totalCustomers = Customer::where('tenant_id', $tenantId)
            ->when($filters['status'] ?? null, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['type'] ?? null, function($query, $type) {
                $query->where('type', $type);
            })
            ->count();

        return [
            'total_customers' => $totalCustomers,
            'inconsistent_customers' => $inconsistentCustomers,
            'consistency_rate' => $totalCustomers > 0 ? (($totalCustomers - $inconsistentCustomers) / $totalCustomers) * 100 : 0,
        ];
    }

    private function auditDuplicates(int $tenantId, array $filters): array
    {
        // Duplicatas por CPF
        $cpfDuplicates = Customer::where('tenant_id', $tenantId)
            ->whereHas('commonData', function($query) {
                $query->whereNotNull('cpf');
            })
            ->when($filters['status'] ?? null, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['type'] ?? null, function($query, $type) {
                $query->where('type', $type);
            })
            ->groupBy('common_data.cpf')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        // Duplicatas por CNPJ
        $cnpjDuplicates = Customer::where('tenant_id', $tenantId)
            ->whereHas('commonData', function($query) {
                $query->whereNotNull('cnpj');
            })
            ->when($filters['status'] ?? null, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['type'] ?? null, function($query, $type) {
                $query->where('type', $type);
            })
            ->groupBy('common_data.cnpj')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        // Duplicatas por e-mail
        $emailDuplicates = Customer::where('tenant_id', $tenantId)
            ->whereHas('contact', function($query) {
                $query->whereNotNull('email');
            })
            ->when($filters['status'] ?? null, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['type'] ?? null, function($query, $type) {
                $query->where('type', $type);
            })
            ->groupBy('contact.email')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        return [
            'cpf_duplicates' => $cpfDuplicates,
            'cnpj_duplicates' => $cnpjDuplicates,
            'email_duplicates' => $emailDuplicates,
            'total_duplicates' => $cpfDuplicates + $cnpjDuplicates + $emailDuplicates,
        ];
    }

    private function auditDataFormats(int $tenantId, array $filters): array
    {
        $invalidEmails = Customer::where('tenant_id', $tenantId)
            ->whereHas('contact', function($query) {
                $query->whereRaw("NOT email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'");
            })
            ->when($filters['status'] ?? null, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['type'] ?? null, function($query, $type) {
                $query->where('type', $type);
            })
            ->count();

        $invalidPhones = Customer::where('tenant_id', $tenantId)
            ->whereHas('contact', function($query) {
                $query->whereRaw("NOT phone REGEXP '^\\([0-9]{2}\\) [0-9]{4,5}-[0-9]{4}$'");
            })
            ->when($filters['status'] ?? null, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['type'] ?? null, function($query, $type) {
                $query->where('type', $type);
            })
            ->count();

        return [
            'invalid_emails' => $invalidEmails,
            'invalid_phones' => $invalidPhones,
            'total_format_issues' => $invalidEmails + $invalidPhones,
        ];
    }

    private function auditRelationships(int $tenantId, array $filters): array
    {
        $customersWithoutCommonData = Customer::where('tenant_id', $tenantId)
            ->doesntHave('commonData')
            ->when($filters['status'] ?? null, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['type'] ?? null, function($query, $type) {
                $query->where('type', $type);
            })
            ->count();

        $customersWithoutContact = Customer::where('tenant_id', $tenantId)
            ->doesntHave('contact')
            ->when($filters['status'] ?? null, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['type'] ?? null, function($query, $type) {
                $query->where('type', $type);
            })
            ->count();

        $customersWithoutAddress = Customer::where('tenant_id', $tenantId)
            ->doesntHave('address')
            ->when($filters['status'] ?? null, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['type'] ?? null, function($query, $type) {
                $query->where('type', $type);
            })
            ->count();

        return [
            'without_common_data' => $customersWithoutCommonData,
            'without_contact' => $customersWithoutContact,
            'without_address' => $customersWithoutAddress,
            'total_relationship_issues' => $customersWithoutCommonData + $customersWithoutContact + $customersWithoutAddress,
        ];
    }

    private function generateOverallQualityReport(array $auditResults): array
    {
        $totalCustomers = $auditResults['completeness']['total_customers'] ?? 0;

        $completenessScore = $auditResults['completeness']['completeness_rate'] ?? 0;
        $consistencyScore = $auditResults['consistency']['consistency_rate'] ?? 0;
        $duplicateScore = $totalCustomers > 0 ? ((($totalCustomers * 3) - $auditResults['duplicates']['total_duplicates']) / ($totalCustomers * 3)) * 100 : 100;
        $formatScore = $totalCustomers > 0 ? ((($totalCustomers * 2) - $auditResults['formats']['total_format_issues']) / ($totalCustomers * 2)) * 100 : 100;
        $relationshipScore = $totalCustomers > 0 ? ((($totalCustomers * 3) - $auditResults['relationships']['total_relationship_issues']) / ($totalCustomers * 3)) * 100 : 100;

        $overallScore = ($completenessScore + $consistencyScore + $duplicateScore + $formatScore + $relationshipScore) / 5;

        return [
            'overall_score' => round($overallScore, 2),
            'completeness_score' => round($completenessScore, 2),
            'consistency_score' => round($consistencyScore, 2),
            'duplicate_score' => round($duplicateScore, 2),
            'format_score' => round($formatScore, 2),
            'relationship_score' => round($relationshipScore, 2),
            'quality_level' => $this->getQualityLevel($overallScore),
        ];
    }

    private function generateAuditSummary(array $auditResults): array
    {
        return [
            'total_customers_audited' => $auditResults['completeness']['total_customers'] ?? 0,
            'customers_with_issues' => $this->calculateTotalCustomersWithIssues($auditResults),
            'critical_issues' => $this->countCriticalIssues($auditResults),
            'recommendations_count' => $this->generateAuditRecommendations($auditResults),
        ];
    }

    private function applyCorrection(Customer $customer, string $field, array $correction): ServiceResult
    {
        try {
            switch ($field) {
                case 'email':
                    $customer->contact->update(['email' => $correction['value']]);
                    break;
                case 'phone':
                    $customer->contact->update(['phone' => $correction['value']]);
                    break;
                case 'address':
                    $customer->address->update($correction['value']);
                    break;
                case 'cpf':
                    $customer->commonData->update(['cpf' => $correction['value']]);
                    break;
                case 'cnpj':
                    $customer->commonData->update(['cnpj' => $correction['value']]);
                    break;
                default:
                    return $this->error('Campo não pode ser corrigido', OperationStatus::INVALID_DATA);
            }

            return $this->success(null, 'Correção aplicada com sucesso');

        } catch (Exception $e) {
            return $this->error('Erro ao aplicar correção: ' . $e->getMessage(), OperationStatus::DATABASE_ERROR);
        }
    }

    private function standardizeNames(Customer $customer): array
    {
        $changes = [];

        // Padronizar nomes para título
        if ($customer->commonData?->first_name) {
            $originalName = $customer->commonData->first_name;
            $standardizedName = mb_convert_case($originalName, MB_CASE_TITLE, 'UTF-8');

            if ($originalName !== $standardizedName) {
                $customer->commonData->update(['first_name' => $standardizedName]);
                $changes['first_name'] = ['from' => $originalName, 'to' => $standardizedName];
            }
        }

        if ($customer->commonData?->last_name) {
            $originalName = $customer->commonData->last_name;
            $standardizedName = mb_convert_case($originalName, MB_CASE_TITLE, 'UTF-8');

            if ($originalName !== $standardizedName) {
                $customer->commonData->update(['last_name' => $standardizedName]);
                $changes['last_name'] = ['from' => $originalName, 'to' => $standardizedName];
            }
        }

        if ($customer->commonData?->company_name) {
            $originalName = $customer->commonData->company_name;
            $standardizedName = mb_convert_case($originalName, MB_CASE_TITLE, 'UTF-8');

            if ($originalName !== $standardizedName) {
                $customer->commonData->update(['company_name' => $standardizedName]);
                $changes['company_name'] = ['from' => $originalName, 'to' => $standardizedName];
            }
        }

        return [
            'applied' => !empty($changes),
            'changes' => $changes,
        ];
    }

    private function standardizeAddresses(Customer $customer): array
    {
        $changes = [];

        // Padronizar endereço
        if ($customer->address?->address) {
            $originalAddress = $customer->address->address;
            $standardizedAddress = mb_convert_case($originalAddress, MB_CASE_TITLE, 'UTF-8');

            if ($originalAddress !== $standardizedAddress) {
                $customer->address->update(['address' => $standardizedAddress]);
                $changes['address'] = ['from' => $originalAddress, 'to' => $standardizedAddress];
            }
        }

        // Padronizar cidade
        if ($customer->address?->city) {
            $originalCity = $customer->address->city;
            $standardizedCity = mb_convert_case($originalCity, MB_CASE_TITLE, 'UTF-8');

            if ($originalCity !== $standardizedCity) {
                $customer->address->update(['city' => $standardizedCity]);
                $changes['city'] = ['from' => $originalCity, 'to' => $standardizedCity];
            }
        }

        return [
            'applied' => !empty($changes),
            'changes' => $changes,
        ];
    }

    private function standardizeContacts(Customer $customer): array
    {
        $changes = [];

        // Padronizar e-mail para minúsculas
        if ($customer->contact?->email) {
            $originalEmail = $customer->contact->email;
            $standardizedEmail = strtolower($originalEmail);

            if ($originalEmail !== $standardizedEmail) {
                $customer->contact->update(['email' => $standardizedEmail]);
                $changes['email'] = ['from' => $originalEmail, 'to' => $standardizedEmail];
            }
        }

        return [
            'applied' => !empty($changes),
            'changes' => $changes,
        ];
    }

    private function standardizeDocuments(Customer $customer): array
    {
        $changes = [];

        // Padronizar CPF
        if ($customer->commonData?->cpf) {
            $originalCpf = $customer->commonData->cpf;
            $standardizedCpf = preg_replace('/[^0-9]/', '', $originalCpf);

            if ($originalCpf !== $standardizedCpf) {
                $customer->commonData->update(['cpf' => $standardizedCpf]);
                $changes['cpf'] = ['from' => $originalCpf, 'to' => $standardizedCpf];
            }
        }

        // Padronizar CNPJ
        if ($customer->commonData?->cnpj) {
            $originalCnpj = $customer->commonData->cnpj;
            $standardizedCnpj = preg_replace('/[^0-9]/', '', $originalCnpj);

            if ($originalCnpj !== $standardizedCnpj) {
                $customer->commonData->update(['cnpj' => $standardizedCnpj]);
                $changes['cnpj'] = ['from' => $originalCnpj, 'to' => $standardizedCnpj];
            }
        }

        return [
            'applied' => !empty($changes),
            'changes' => $changes,
        ];
    }

    private function standardizeDates(Customer $customer): array
    {
        $changes = [];

        // Padronizar data de nascimento
        if ($customer->commonData?->birth_date) {
            $originalDate = $customer->commonData->birth_date;
            $standardizedDate = $originalDate->format('Y-m-d');

            if ($originalDate->format('Y-m-d') !== $standardizedDate) {
                $customer->commonData->update(['birth_date' => $standardizedDate]);
                $changes['birth_date'] = ['from' => $originalDate->format('Y-m-d'), 'to' => $standardizedDate];
            }
        }

        return [
            'applied' => !empty($changes),
            'changes' => $changes,
        ];
    }

    private function isValidCPF(string $cpf): bool
    {
        // Implementação do algoritmo de validação de CPF
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) !== 11) return false;
        if (preg_match('/^(\d)\1+$/', $cpf)) return false;

        // Cálculo dos dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }

        return true;
    }

    private function isValidCNPJ(string $cnpj): bool
    {
        // Implementação do algoritmo de validação de CNPJ
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) !== 14) return false;
        if (preg_match('/^(\d)\1+$/', $cnpj)) return false;

        // Cálculo dos dígitos verificadores
        $length = strlen($cnpj);
        $weight = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($t = 0; $t < 2; $t++) {
            $sum = 0;
            $weightIndex = 0;

            for ($i = $length - 2 + $t; $i >= 0; $i--) {
                $sum += $cnpj[$i] * $weight[$weightIndex++];
            }

            $sum = 11 - ($sum % 11);
            $digit = $sum > 9 ? 0 : $sum;

            if ($cnpj[$length - 1 - $t] != $digit) return false;
        }

        return true;
    }

    private function getValidStates(): array
    {
        return [
            'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG',
            'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
        ];
    }

    private function getIssuePriority(string $category): string
    {
        return match ($category) {
            'mandatory_fields' => 'high',
            'duplicates' => 'high',
            'relationships' => 'high',
            'data_consistency' => 'medium',
            'data_formats' => 'low',
            default => 'medium',
        };
    }

    private function getRecommendedAction(string $category, string $issue): string
    {
        return match ($category) {
            'mandatory_fields' => 'Preencher campo obrigatório',
            'duplicates' => 'Verificar e remover duplicidade',
            'relationships' => 'Corrigir relacionamento',
            'data_consistency' => 'Ajustar consistência de dados',
            'data_formats' => 'Corrigir formato de dado',
            default => 'Verificar e corrigir',
        };
    }

    private function getQualityLevel(float $score): string
    {
        if ($score >= 90) return 'Excelente';
        if ($score >= 80) return 'Bom';
        if ($score >= 70) return 'Regular';
        if ($score >= 60) return 'Ruim';
        return 'Péssimo';
    }

    private function calculateTotalCustomersWithIssues(array $auditResults): int
    {
        return $auditResults['completeness']['incomplete_customers'] +
               $auditResults['consistency']['inconsistent_customers'] +
               $auditResults['duplicates']['total_duplicates'] +
               $auditResults['formats']['total_format_issues'] +
               $auditResults['relationships']['total_relationship_issues'];
    }

    private function countCriticalIssues(array $auditResults): int
    {
        return $auditResults['completeness']['incomplete_customers'] +
               $auditResults['duplicates']['total_duplicates'] +
               $auditResults['relationships']['total_relationship_issues'];
    }

    private function generateAuditRecommendations(array $auditResults): int
    {
        $recommendations = 0;

        if ($auditResults['completeness']['incomplete_customers'] > 0) $recommendations++;
        if ($auditResults['duplicates']['total_duplicates'] > 0) $recommendations++;
        if ($auditResults['relationships']['total_relationship_issues'] > 0) $recommendations++;

        return $recommendations;
    }
}
```

### **✅ Sistema de Auditoria Automática**

```php
class CustomerDataQualityAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->auditTenantDataQuality($tenant);
        }
    }

    private function auditTenantDataQuality(Tenant $tenant): void
    {
        $dataQualityService = app(CustomerDataQualityService::class);

        $result = $dataQualityService->auditCustomerData($tenant->id);

        if ($result->isSuccess()) {
            $this->generateQualityReport($tenant, $result->getData());
            $this->sendQualityAlerts($tenant, $result->getData());
        }
    }

    private function generateQualityReport(Tenant $tenant, array $auditData): void
    {
        // Gerar relatório de qualidade de dados
        $report = CustomerDataQualityReport::create([
            'tenant_id' => $tenant->id,
            'overall_score' => $auditData['overall_report']['overall_score'],
            'completeness_score' => $auditData['overall_report']['completeness_score'],
            'consistency_score' => $auditData['overall_report']['consistency_score'],
            'duplicate_score' => $auditData['overall_report']['duplicate_score'],
            'format_score' => $auditData['overall_report']['format_score'],
            'relationship_score' => $auditData['overall_report']['relationship_score'],
            'quality_level' => $auditData['overall_report']['quality_level'],
            'total_customers' => $auditData['summary']['total_customers_audited'],
            'customers_with_issues' => $auditData['summary']['customers_with_issues'],
            'critical_issues' => $auditData['summary']['critical_issues'],
            'data' => $auditData,
        ]);

        // Enviar relatório por e-mail se houver problemas críticos
        if ($auditData['summary']['critical_issues'] > 0) {
            SendDataQualityReport::dispatch($tenant, $report);
        }
    }

    private function sendQualityAlerts(Tenant $tenant, array $auditData): void
    {
        $criticalThreshold = 60; // Score crítico abaixo de 60%

        if ($auditData['overall_report']['overall_score'] < $criticalThreshold) {
            // Enviar alerta de qualidade crítica
            SendDataQualityAlert::dispatch($tenant, $auditData);
        }
    }
}
```

### **✅ Sistema de Correção Automática**

```php
class CustomerDataAutoCorrectionService extends AbstractBaseService
{
    public function autoCorrectCustomerData(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $corrections = [];

            // 1. Correções automáticas de formatos
            $formatCorrections = $this->autoCorrectFormats($customer);
            $corrections['formats'] = $formatCorrections;

            // 2. Correções automáticas de padronização
            $standardizationCorrections = $this->autoCorrectStandardization($customer);
            $corrections['standardization'] = $standardizationCorrections;

            // 3. Correções automáticas de consistência
            $consistencyCorrections = $this->autoCorrectConsistency($customer);
            $corrections['consistency'] = $consistencyCorrections;

            // 4. Validar após correções
            $validationResult = $this->validateCustomerData($customer);

            return $this->success([
                'corrections_applied' => $corrections,
                'validation_after_correction' => $validationResult->getData(),
            ], 'Correções automáticas aplicadas');
        });
    }

    private function autoCorrectFormats(Customer $customer): array
    {
        $corrections = [];

        // Corrigir formato de e-mail
        if ($customer->contact?->email && !filter_var($customer->contact->email, FILTER_VALIDATE_EMAIL)) {
            $correctedEmail = $this->correctEmailFormat($customer->contact->email);
            if ($correctedEmail) {
                $customer->contact->update(['email' => $correctedEmail]);
                $corrections['email'] = [
                    'original' => $customer->contact->email,
                    'corrected' => $correctedEmail,
                ];
            }
        }

        // Corrigir formato de telefone
        if ($customer->contact?->phone && !preg_match('/^\(\d{2}\)\s\d{4,5}-\d{4}$/', $customer->contact->phone)) {
            $correctedPhone = $this->correctPhoneFormat($customer->contact->phone);
            if ($correctedPhone) {
                $customer->contact->update(['phone' => $correctedPhone]);
                $corrections['phone'] = [
                    'original' => $customer->contact->phone,
                    'corrected' => $correctedPhone,
                ];
            }
        }

        return $corrections;
    }

    private function autoCorrectStandardization(Customer $customer): array
    {
        $corrections = [];

        // Padronizar nomes automaticamente
        if ($customer->commonData?->first_name) {
            $standardizedName = mb_convert_case($customer->commonData->first_name, MB_CASE_TITLE, 'UTF-8');
            if ($customer->commonData->first_name !== $standardizedName) {
                $customer->commonData->update(['first_name' => $standardizedName]);
                $corrections['first_name'] = [
                    'original' => $customer->commonData->first_name,
                    'standardized' => $standardizedName,
                ];
            }
        }

        // Padronizar e-mails para minúsculas
        if ($customer->contact?->email) {
            $lowercaseEmail = strtolower($customer->contact->email);
            if ($customer->contact->email !== $lowercaseEmail) {
                $customer->contact->update(['email' => $lowercaseEmail]);
                $corrections['email'] = [
                    'original' => $customer->contact->email,
                    'standardized' => $lowercaseEmail,
                ];
            }
        }

        return $corrections;
    }

    private function autoCorrectConsistency(Customer $customer): array
    {
        $corrections = [];

        // Corrigir consistência entre tipo de cliente e documentos
        if ($customer->type === 'individual' && $customer->commonData?->cnpj) {
            // Remover CNPJ de cliente PF
            $customer->commonData->update(['cnpj' => null]);
            $corrections['cnpj_removal'] = [
                'reason' => 'Cliente PF não deve ter CNPJ',
                'removed' => $customer->commonData->cnpj,
            ];
        }

        if ($customer->type === 'company' && $customer->commonData?->cpf) {
            // Remover CPF de cliente PJ
            $customer->commonData->update(['cpf' => null]);
            $corrections['cpf_removal'] = [
                'reason' => 'Cliente PJ não deve ter CPF',
                'removed' => $customer->commonData->cpf,
            ];
        }

        return $corrections;
    }

    private function correctEmailFormat(string $email): ?string
    {
        // Lógica simples de correção de e-mail
        // Pode ser expandida com regras mais complexas
        $email = trim($email);
        $email = strtolower($email);

        // Corrigir domínios comuns
        $commonDomains = [
            'gmail.com' => 'gmail.com',
            'gmail.con' => 'gmail.com',
            'gmail.co' => 'gmail.com',
            'hotmail.com' => 'hotmail.com',
            'hotmail.con' => 'hotmail.com',
            'yahoo.com' => 'yahoo.com',
            'yahoo.con' => 'yahoo.com',
        ];

        foreach ($commonDomains as $incorrect => $correct) {
            if (strpos($email, $incorrect) !== false) {
                $email = str_replace($incorrect, $correct, $email);
            }
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function correctPhoneFormat(string $phone): ?string
    {
        // Lógica simples de correção de telefone
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 10) {
            // Telefone fixo: (XX) XXXX-XXXX
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6, 4);
        } elseif (strlen($phone) === 11) {
            // Celular: (XX) XXXXX-XXXX
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7, 4);
        }

        return null;
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Qualidade de Dados**

```php
public function testCustomerDataValidation()
{
    $customer = Customer::factory()->create();

    $result = $this->dataQualityService->validateCustomerData($customer);
    $this->assertTrue($result->isSuccess());

    $validationData = $result->getData();
    $this->assertArrayHasKey('valid', $validationData);
    $this->assertArrayHasKey('quality_score', $validationData);
    $this->assertArrayHasKey('validation_results', $validationData);
    $this->assertArrayHasKey('issues', $validationData);
    $this->assertArrayHasKey('recommendations', $validationData);
}

public function testCustomerDataValidationWithIssues()
{
    // Criar cliente com dados inválidos
    $customer = Customer::factory()->create([
        'type' => 'individual',
        'status' => 'active',
    ]);

    // Remover dados obrigatórios para testar validação
    $customer->commonData->update(['cpf' => null]);
    $customer->contact->update(['email' => 'email-invalido']);

    $result = $this->dataQualityService->validateCustomerData($customer);
    $this->assertFalse($result->isSuccess());

    $validationData = $result->getData();
    $this->assertFalse($validationData['valid']);
    $this->assertLessThan(100, $validationData['quality_score']);
    $this->assertNotEmpty($validationData['issues']);
}

public function testCustomerDataAudit()
{
    $tenant = Tenant::factory()->create();
    Customer::factory()->count(10)->create(['tenant_id' => $tenant->id]);

    $result = $this->dataQualityService->auditCustomerData($tenant->id);
    $this->assertTrue($result->isSuccess());

    $auditData = $result->getData();
    $this->assertArrayHasKey('audit_results', $auditData);
    $this->assertArrayHasKey('overall_report', $auditData);
    $this->assertArrayHasKey('summary', $auditData);
}

public function testDataStandardization()
{
    $customer = Customer::factory()->create([
        'common_data' => [
            'first_name' => 'joão',
            'last_name' => 'silva',
        ],
        'contact' => [
            'email' => 'JOAO@EXAMPLE.COM',
        ],
    ]);

    $result = $this->dataQualityService->standardizeCustomerData($customer);
    $this->assertTrue($result->isSuccess());

    $standardizedCustomer = $result->getData()['customer_updated'];
    $this->assertEquals('João', $standardizedCustomer->commonData->first_name);
    $this->assertEquals('Silva', $standardizedCustomer->commonData->last_name);
    $this->assertEquals('joao@example.com', $standardizedCustomer->contact->email);
}

public function testDataAutoCorrection()
{
    $customer = Customer::factory()->create([
        'contact' => [
            'email' => 'joao@examp.le',
            'phone' => '11987654321',
        ],
    ]);

    $result = $this->autoCorrectionService->autoCorrectCustomerData($customer);
    $this->assertTrue($result->isSuccess());

    $corrections = $result->getData()['corrections_applied'];
    $this->assertArrayHasKey('formats', $corrections);
    $this->assertArrayHasKey('standardization', $corrections);
}
```

### **✅ Testes de Auditoria Automática**

```php
public function testDataQualityAuditJob()
{
    Queue::fake();

    // Criar tenants e clientes
    $tenant = Tenant::factory()->create();
    Customer::factory()->count(5)->create(['tenant_id' => $tenant->id]);

    // Disparar job
    CustomerDataQualityAuditJob::dispatch();

    // Verificar se o job foi enfileirado
    Queue::assertPushed(CustomerDataQualityAuditJob::class);

    // Executar job
    $job = new CustomerDataQualityAuditJob();
    $job->handle();

    // Verificar se o relatório foi gerado
    $this->assertDatabaseHas('customer_data_quality_reports', [
        'tenant_id' => $tenant->id,
    ]);
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar CustomerDataQualityService básico
- [ ] Criar sistema de validação de campos obrigatórios
- [ ] Implementar validação de formatos de dados
- [ ] Sistema básico de auditoria

### **Fase 2: Core Features**
- [ ] Implementar validação de consistência de dados
- [ ] Sistema de detecção de duplicidades
- [ ] Validação de relacionamentos
- [ ] Cálculo de score de qualidade

### **Fase 3: Advanced Features**
- [ ] Sistema de correção automática
- [ ] Auditoria programada (jobs)
- [ ] Relatórios de qualidade de dados
- [ ] Alertas de qualidade crítica

### **Fase 4: Integration**
- [ ] Integração com processos de importação
- [ ] Sistema de limpeza de dados em lote
- [ ] Dashboard de qualidade de dados
- [ ] API para auditoria externa

## 📚 Documentação Relacionada

- [CustomerDataQualityService](../../app/Services/Domain/CustomerDataQualityService.php)
- [CustomerDataQualityAuditJob](../../app/Jobs/CustomerDataQualityAuditJob.php)
- [CustomerDataAutoCorrectionService](../../app/Services/Domain/CustomerDataAutoCorrectionService.php)
- [CustomerDataQualityReport](../../app/Models/CustomerDataQualityReport.php)

## 🎯 Benefícios

### **✅ Qualidade de Dados**
- Dados consistentes e confiáveis
- Redução de erros e inconsistências
- Padronização de formatos e valores
- Detecção precoce de problemas

### **✅ Conformidade**
- Cumprimento de requisitos regulatórios
- Auditoria contínua de qualidade
- Relatórios de conformidade
- Histórico de correções e melhorias

### **✅ Eficiência Operacional**
- Redução de retrabalho
- Processos automatizados de correção
- Identificação rápida de problemas
- Melhor tomada de decisão baseada em dados

### **✅ Experiência do Usuário**
- Dados corretos e consistentes
- Redução de erros no atendimento
- Processos mais ágeis
- Maior confiança nos sistemas

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
