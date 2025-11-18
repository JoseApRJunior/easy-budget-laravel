# Documentação de Faturas Automáticas e Manuais

## Visão Geral

Este documento descreve a implementação do sistema de faturas automáticas e manuais para o Easy Budget Laravel, incluindo a geração automática de faturas ao finalizar serviços e a criação manual de faturas parciais.

## Funcionalidades Implementadas

### 1. Geração Automática de Faturas

#### Descrição
Quando um serviço muda para o status "completed" (finalizado), uma fatura é automaticamente gerada com:
- Data de vencimento de 30 dias
- Valor total do serviço
- Marcação como fatura automática (`is_automatic = true`)
- Status pendente para pagamento

#### Componentes

**ServiceObserver** (`app/Observers/ServiceObserver.php`)
- Monitora mudanças de status nos serviços
- Dispara geração automática quando status muda para "completed"
- Previne duplicação de faturas para o mesmo serviço
- Implementa tratamento de erros robusto

**InvoiceService** (`app/Services/Domain/InvoiceService.php`)
- Método `createInvoiceFromService()` para criar faturas a partir de serviços
- Suporte para faturas automáticas e manuais
- Validação de dados e cálculo de totais

#### Configuração

O observer é registrado em `app/Providers/EventServiceProvider.php`:

```php
public function boot(): void
{
    parent::boot();
    
    // Registrar observer para geração automática de faturas
    Service::observe(ServiceObserver::class);
    
    // ... resto do código
}
```

### 2. Endpoints de Faturas Manuais

#### POST /provider/invoices/services/{serviceCode}/manual

**Descrição**: Cria uma fatura manual a partir de um serviço existente.

**Parâmetros**:
- `serviceCode` (string, obrigatório): Código do serviço
- `issue_date` (date, obrigatório): Data de emissão
- `due_date` (date, obrigatório): Data de vencimento (deve ser >= issue_date)
- `notes` (string, opcional): Observações da fatura
- `items` (array, opcional): Itens específicos para faturar

**Exemplo de Requisição**:
```json
{
    "issue_date": "2025-11-18",
    "due_date": "2025-12-18",
    "notes": "Fatura manual para pagamento parcial",
    "items": [
        {
            "product_id": 1,
            "quantity": 2,
            "unit_value": 150.00
        }
    ]
}
```

**Respostas**:
- `200 OK`: Fatura criada com sucesso
- `400 Bad Request`: Dados inválidos
- `404 Not Found`: Serviço não encontrado
- `500 Internal Server Error`: Erro no processamento

### 3. Interface de Faturas Parciais

#### GET /provider/invoices/services/{serviceCode}/create-partial

**Descrição**: Exibe interface para criar fatura parcial com seleção de itens.

**Funcionalidades**:
- Seleção individual de itens do serviço
- Quantidade ajustável para cada item
- Cálculo automático do total selecionado
- Validação de datas (vencimento >= emissão)
- Prevenção de faturas vazias

#### Características da Interface
- **Tabela de Itens**: Lista todos os itens do serviço com:
  - Checkbox para seleção
  - Quantidade total vs quantidade a faturar
  - Preço unitário e total por item
  - Cálculo dinâmico do total parcial
- **Seleção em Massa**: Checkbox "Selecionar Todos"
- **Validações**: 
  - Quantidade máxima não excede o total
  - Data de vencimento >= data de emissão
  - Pelo menos um item deve ser selecionado

### 4. Rotas Disponíveis

```php
// Rotas de Faturas
Route::prefix('invoices')->name('invoices.')->group(function () {
    // Dashboard e listagem
    Route::get('/dashboard', [InvoiceController::class, 'dashboard'])->name('dashboard');
    Route::get('/', [InvoiceController::class, 'index'])->name('index');
    
    // Criação de faturas
    Route::get('/create', [InvoiceController::class, 'create'])->name('create');
    Route::post('/', [InvoiceController::class, 'store'])->name('store');
    
    // Faturas a partir de orçamentos
    Route::get('/budgets/{budget}/create', [InvoiceController::class, 'createFromBudget'])->name('create.from-budget');
    Route::post('/budgets/{budget}', [InvoiceController::class, 'storeFromBudget'])->name('store.from-budget');
    
    // Faturas a partir de serviços
    Route::get('/services/{serviceCode}/create', [InvoiceController::class, 'createFromService'])->name('create.from-service');
    Route::get('/services/{serviceCode}/create-partial', [InvoiceController::class, 'createPartialFromService'])->name('create.partial-from-service');
    Route::post('/services/{serviceCode}/manual', [InvoiceController::class, 'storeManualFromService'])->name('store.manual-from-service');
    
    // Gestão de faturas existentes
    Route::get('/{code}', [InvoiceController::class, 'show'])->name('show');
    Route::get('/{code}/edit', [InvoiceController::class, 'edit'])->name('edit');
    Route::put('/{code}', [InvoiceController::class, 'update'])->name('update');
    Route::delete('/{code}', [InvoiceController::class, 'destroy'])->name('destroy');
    
    // Funcionalidades adicionais
    Route::get('/search/ajax', [InvoiceController::class, 'search'])->name('search');
    Route::get('/{code}/print', [InvoiceController::class, 'print'])->name('print');
    Route::get('/export', [InvoiceController::class, 'export'])->name('export');
});
```

### 5. Modelo de Dados

#### Tabela `invoices` (Adição)
- `is_automatic` (boolean, default: false): Indica se a fatura foi gerada automaticamente
- Índice em `is_automatic` para consultas eficientes

#### Migração
```php
Schema::table('invoices', function (Blueprint $table) {
    $table->boolean('is_automatic')->default(false)->after('notes');
    $table->index('is_automatic');
});
```

### 6. Fluxo de Trabalho

#### Fatura Automática
1. Serviço é marcado como "completed"
2. `ServiceObserver` detecta a mudança
3. Verifica se já existe fatura para o serviço
4. Cria fatura com `is_automatic = true`
5. Define data de vencimento (30 dias)
6. Copia itens do serviço para a fatura

#### Fatura Manual
1. Usuário acessa interface de fatura parcial
2. Seleciona itens e quantidades
3. Define datas de emissão e vencimento
4. Submete formulário
5. Sistema cria fatura com `is_automatic = false`

### 7. Testes

#### Testes Unitários
- `ServiceObserverTest`: Testa geração automática de faturas
- Validações de duplicação, erros e casos extremos

#### Testes de Integração
- Verificação de rotas e controllers
- Validação de permissões e autenticação

### 8. Segurança e Validações

#### Validações Implementadas
- **Tenant Scoping**: Usuários só acessam dados do seu tenant
- **Autenticação**: Todas as rotas requerem autenticação
- **Autorização**: Verificação de permissões por tenant
- **Validação de Dados**: 
  - Código de serviço válido
  - Datas coerentes (vencimento >= emissão)
  - Quantidades positivas
  - Itens existentes

#### Tratamento de Erros
- **404 Not Found**: Serviço não encontrado ou código inválido
- **403 Forbidden**: Serviço de outro tenant
- **422 Unprocessable Entity**: Dados de entrada inválidos
- **500 Internal Server Error**: Erros de processamento

### 9. Logs e Monitoramento

#### Logs Implementados
- Início da geração automática de fatura
- Sucesso na criação de fatura
- Tentativa de duplicação
- Erros durante o processo
- Informações contextuais (service_id, tenant_id, etc.)

#### Exemplos de Logs
```
[INFO] Iniciando geração automática de fatura para serviço
[INFO] Fatura automática gerada com sucesso
[WARNING] Fatura já existe para este serviço, ignorando geração automática
[ERROR] Erro ao gerar fatura automática
```

### 10. Considerações de Performance

#### Otimizações
- Índice em `is_automatic` para consultas rápidas
- Verificação de existência antes de criar fatura
- Transações de banco de dados para consistência
- Carregamento seletivo de relacionamentos

#### Escalabilidade
- Observer pattern para processamento assíncrono
- Prevenção de duplicação evita processamento redundante
- Logs detalhados para debugging e monitoramento

## Conclusão

O sistema implementado oferece:
- ✅ Geração automática de faturas ao finalizar serviços
- ✅ Interface intuitiva para faturas parciais
- ✅ Validações robustas e segurança por tenant
- ✅ Tratamento completo de erros e logs
- ✅ Testes unitários para garantir funcionalidade
- ✅ Documentação completa dos endpoints e fluxos

O sistema está pronto para uso em produção com suporte total para faturas automáticas e manuais, garantindo flexibilidade e segurança no processo de faturamento.