# 🔧 GUIA DE MANUTENÇÃO FUTURA - EASY BUDGET LARAVEL

## 📋 **VISÃO GERAL**

Este guia fornece diretrizes para manutenção contínua do sistema Easy Budget Laravel, garantindo que as correções implementadas sejam preservadas e o código mantenha alta qualidade.

---

## 🛠️ **1. MANUTENÇÃO DE MODELOS**

### **1.1 Adicionando Novos Modelos**

**✅ Procedimento Obrigatório:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped; // Se multi-tenant

class NovoModelo extends Model
{
    use TenantScoped; // Se necessário

    protected $table = 'nome_tabela';

    protected $fillable = [
        'campo1',
        'campo2',
        // ... outros campos
    ];

    protected $casts = [
        'campo_booleano' => 'boolean',
        'campo_data' => 'datetime',
        'campo_decimal' => 'decimal:2',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    // OBRIGATÓRIO: Implementar BusinessRules
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'campo_obrigatorio' => 'required|string|max:255',
            'campo_unico' => 'required|string|unique:tabela,campo_unico',
            'campo_email' => 'required|email|max:255|unique:tabela,campo_email',
        ];
    }

    // OPCIONAL: Relacionamentos
    public function relacao(): BelongsTo
    {
        return $this->belongsTo(OutroModelo::class);
    }
}
```

### **1.2 Modificando Modelos Existentes**

**✅ Regras para Modificações:**

1. **NUNCA** remover BusinessRules existentes
2. **SEMPRE** adicionar novas validações se necessário
3. **VALIDAR** relacionamentos antes de modificar
4. **TESTAR** mudanças em ambiente de desenvolvimento
5. **ATUALIZAR** documentação se houver mudanças significativas

---

## 🗄️ **2. MANUTENÇÃO DO BANCO DE DADOS**

### **2.1 Criando Novas Migrations**

**✅ Estrutura Obrigatória:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nova_tabela', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            // ... outros campos
            $table->timestamps();

            // Índices recomendados
            $table->index('tenant_id');
            $table->unique(['tenant_id', 'campo_unico']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nova_tabela');
    }
};
```

### **2.2 Modificando Migrations Existentes**

**⚠️ CUIDADO:** Modificar migrations existentes pode causar problemas em produção.

**✅ Quando Modificar:**

-  Corrigir bugs críticos
-  Adicionar índices de performance
-  Ajustar constraints que não afetam dados existentes

**❌ NUNCA Modificar:**

-  Remover colunas com dados
-  Alterar tipos de dados que causem perda de informação
-  Remover foreign keys sem análise de impacto

---

## 🔍 **3. VALIDAÇÃO E TESTES**

### **3.1 Validação de Relacionamentos**

**✅ Queries para Testar:**

```php
// Testar relacionamentos
$rolePermission = RolePermission::first();
echo $rolePermission->role->name; // Deve funcionar
echo $rolePermission->permission->name; // Deve funcionar
echo $rolePermission->tenant->name; // Deve funcionar

$budgetStatus = BudgetStatus::first();
$budgetStatus->budgets()->count(); // Deve retornar número

$invoiceStatus = InvoiceStatus::first();
$invoiceStatus->invoices()->count(); // Deve retornar número
```

### **3.2 Validação de BusinessRules**

**✅ Teste de Validações:**

```php
use App\Models\Modelo::businessRules;

// Verificar se BusinessRules existem
$regras = Modelo::businessRules();
assert(!empty($regras), 'BusinessRules devem estar implementadas');

// Verificar validações de FK
assert(isset($regras['tenant_id']), 'tenant_id deve ter validação');
assert(isset($regras['customer_id']), 'customer_id deve ter validação quando aplicável');
```

### **3.3 Teste de Sintaxe**

**✅ Verificação Contínua:**

```bash
# Verificar sintaxe de todos os modelos
find app/Models -name "*.php" -exec php -l {} \;

# Verificar se BusinessRules existem em todos os modelos
grep -r "public static function businessRules()" app/Models/ | wc -l
# Deve retornar 43 (todos os modelos)
```

---

## 📊 **4. MONITORAMENTO**

### **4.1 Monitoramento de Performance**

**✅ Queries para Monitorar:**

```sql
-- Verificar uso de índices
SHOW INDEX FROM tabela_principal;

-- Verificar queries lentas
SELECT * FROM middleware_metrics_history
WHERE response_time > 1.0
ORDER BY created_at DESC;

-- Verificar uso de memória
SELECT AVG(memory_usage) as avg_memory
FROM middleware_metrics_history
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY);
```

### **4.2 Monitoramento de Integridade**

**✅ Verificações Periódicas:**

```php
// Verificar integridade referencial
$orphanedRecords = DB::table('tabela_filha')
    ->leftJoin('tabela_pai', 'tabela_filha.pai_id', '=', 'tabela_pai.id')
    ->whereNull('tabela_pai.id')
    ->count();

assert($orphanedRecords == 0, 'Não deve haver registros órfãos');
```

---

## 🔒 **5. SEGURANÇA**

### **5.1 Validações de Segurança**

**✅ Implementar Sempre:**

-  Validação de todas as entradas
-  Sanitização de dados de saída
-  Autorização adequada
-  Proteção contra SQL injection (automática com Eloquent)
-  Proteção contra XSS (com Blade)

### **5.2 Auditoria**

**✅ Manter Logs de:**

-  Criação/modificação/exclusão de registros sensíveis
-  Acesso a funcionalidades administrativas
-  Tentativas de acesso não autorizado
-  Mudanças em configurações críticas

---

## 📚 **6. DOCUMENTAÇÃO**

### **6.1 Documentação Obrigatória**

**✅ Para Novos Recursos:**

-  Adicionar comentários PHPDoc em métodos complexos
-  Documentar relacionamentos não óbvios
-  Explicar BusinessRules específicas
-  Documentar constraints de banco

**✅ Para Modificações:**

-  Atualizar changelog (CHANGELOG_CORRECOES_2025.md)
-  Documentar motivo da mudança
-  Indicar impacto em outros módulos
-  Atualizar guias de usuário se necessário

---

## 🚀 **7. DEPLOYMENT**

### **7.1 Procedimentos de Deploy**

**✅ Checklist Pré-Deploy:**

-  [ ] Todos os testes passando
-  [ ] BusinessRules validadas
-  [ ] Relacionamentos testados
-  [ ] Documentação atualizada
-  [ ] Backup do banco realizado
-  [ ] Migration testada em ambiente staging

**✅ Checklist Pós-Deploy:**

-  [ ] Aplicação funcionando normalmente
-  [ ] Logs sem erros críticos
-  [ ] Performance dentro dos parâmetros
-  [ ] Funcionalidades críticas testadas

---

## 🆘 **8. SUPORTE E DEBUG**

### **8.1 Diagnóstico de Problemas**

**✅ Passos para Debug:**

1. **Verificar logs** do Laravel
2. **Testar relacionamentos** manualmente
3. **Validar BusinessRules** com dados de teste
4. **Verificar integridade** do banco
5. **Analisar métricas** de performance

### **8.2 Problemas Comuns**

**🔴 Relacionamentos Não Funcionam:**

-  Verificar se foreign keys estão corretas na migration
-  Confirmar se modelos têm relacionamentos implementados
-  Testar queries manualmente no Tinker

**🔴 BusinessRules Não Validam:**

-  Verificar se método `businessRules()` existe
-  Confirmar se regras estão no formato correto
-  Testar validação manualmente

**🔴 Performance Lenta:**

-  Verificar se índices estão criados
-  Analisar queries N+1
-  Considerar cache para dados estáticos

---

## 📞 **9. CONTATO E SUPORTE**

### **9.1 Equipe Responsável**

-  **Desenvolvedor Principal:** Kilo Code
-  **Documentação:** Arquivos em `/docs/`
-  **Changelogs:** `CHANGELOG_CORRECOES_2025.md`
-  **Verificação Final:** `VERIFICACAO_FINAL_INTEGRIDADE_2025.md`

### **9.2 Procedimentos de Emergência**

1. **Consultar** documentação primeiro
2. **Verificar** changelogs para mudanças recentes
3. **Testar** em ambiente de desenvolvimento
4. **Analisar** logs para identificar causa
5. **Documentar** solução implementada

---

## 🎯 **10. CONCLUSÃO**

Este guia garante que:

-  ✅ **Qualidade** do código seja mantida
-  ✅ **Correções** implementadas sejam preservadas
-  ✅ **Novos recursos** sigam os mesmos padrões
-  ✅ **Manutenção** seja feita de forma segura
-  ✅ **Documentação** esteja sempre atualizada

**Lembre-se:** A manutenção adequada é tão importante quanto a implementação inicial. Siga este guia para garantir a longevidade e estabilidade do sistema.

---

**Versão do Guia:** 1.0.0
**Data de Criação:** 27 de Setembro de 2025
**Status:** ✅ **ATIVO E VÁLIDO**
