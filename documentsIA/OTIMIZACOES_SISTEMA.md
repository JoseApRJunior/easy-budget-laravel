# 🚀 Análise e Recomendações de Otimização - Easy Budget Laravel

## ✅ Otimizações Já Implementadas

### 1. Cache de Roles e Permissions (User Model)
- ✅ Propriedades `$roleCache` e `$permissionCache`
- ✅ Eager loading de `tenant` (`protected $with`)
- ✅ Middleware `OptimizeAuthUser` para carregar roles antecipadamente

---

## 📊 Oportunidades de Otimização Identificadas

### 🔴 CRÍTICO - Alto Impacto

#### 1. **Configuração de Cache**
**Problema:** Cache configurado para `database` (mais lento)
```php
// config/cache.php linha 18
'default' => env('CACHE_STORE', 'database'),
```

**Recomendação:** Usar Redis ou File cache
```env
CACHE_STORE=file  # Ou redis se disponível
```

**Ganho:** ~40-60% melhoria em operações de cache

---

#### 2. **Falta de Índices no Banco de Dados**
**Áreas para verificar:**
- Tabela `users`: `(tenant_id, email)`, `(tenant_id, is_active)`
- Tabela `products`: `(tenant_id, sku)`, `(tenant_id, active)`
- Tabela `product_inventory`: `(product_id, tenant_id)`
- Tabela `inventory_movements`: `(product_id, type, created_at)`
- Tabela `user_roles`: `(user_id, tenant_id, role_id)`
- Tabela `sessions`: `(user_id)`, `(last_activity)`

**Comando para criar migration:**
```bash
php artisan make:migration add_performance_indexes
```

**Ganho:** ~50-70% melhoria em queries

---

#### 3. **Eager Loading em Models**

**Models que precisam de `$with`:**

**Product.php:**
```php
protected $with = ['category'];
```

**ProductInventory.php:**
```php
protected $with = ['product'];
```

**InventoryMovement.php:**
```php
protected $with = ['product', 'user'];
```

**Ganho:** Elimina problema N+1 queries

---

### 🟡 MÉDIO - Impacto Moderado

#### 4. **Otimização de Queries em Controllers**

**InventoryController::index()**
```php
// Atual
$inventories = $query->paginate(15);

// Otimizado
$inventories = $query
    ->with(['product.category'])
    ->paginate(15);
```

**Ganho:** ~30-40% redução de queries

---

#### 5. **Cache de Configurações**
**Produção DEVE ter:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

**Ganho:** ~20-30% melhoria no tempo de resposta

---

#### 6. **Otimização de Session**
**Problema:** Session em database (consulta a cada request)

**Recomendação:**
```env
SESSION_DRIVER=file  # Ou redis se disponível
```

**Ganho:** ~15-25ms por request

---

### 🟢 BAIXO - Melhorias Incrementais

#### 7. **Compressão de Assets**
```bash
npm run build  # Minifica JS/CSS
```

#### 8. **Lazy Loading de Relacionamentos Grandes**
```php
// Em views que não precisam de todos os dados
$product->load('category:id,name'); // Somente campos necessários
```

#### 9. **Query Scopes Otimizados**
```php
// Product.php
public function scopeWithInventoryData($query)
{
    return $query->with(['productInventory' => function ($q) {
        $q->select('id', 'product_id', 'quantity', 'min_quantity');
    }]);
}
```

---

## 🎯 Plano de Ação Recomendado

### Fase 1 - Rápido Ganho (1-2 horas)
1. ✅ Trocar cache para `file`
2. ✅ Trocar session para `file`
3. ✅ Rodar commands de cache em produção
4. ✅ Adicionar `$with` em Product, ProductInventory

### Fase 2 - Médio Prazo (3-5 horas)
1. ⏳ Criar migration com índices
2. ⏳ Adicionar eager loading em controllers
3. ⏳ Otimizar queries grandes

### Fase 3 - Longo Prazo (opcional)
1. ⏳ Implementar Redis para cache
2. ⏳ Implementar Redis para sessions
3. ⏳ Implementar queue para tarefas pesadas

---

## 📈 Ganhos Esperados

### Implementando Fase 1 + 2:
- **Queries duplicadas:** De 4 para 0
- **Tempo de resposta:** De ~550ms para ~150-200ms
- **Queries totais:** De 9 para ~4-5
- **Uso de memória:** Redução de ~20%

---

## 🔍 Comandos para Monitoramento

### Verificar queries lentas:
```bash
# No .env
DB_LOG_SLOW_QUERIES=true
DB_LOG_SLOW_QUERIES_THRESHOLD=100  # ms
```

### Laravel Telescope (já instalado):
```bash
php artisan telescope:install
php artisan migrate
```

### Debug Bar:
- Já está mostrando queries duplicadas ✅

---

## ⚠️ Avisos Importantes

1. **Sempre testar em desenvolvimento antes de produção**
2. **Fazer backup do banco antes de adicionar índices**
3. **Monitorar uso de memória após eager loading**
4. **Cache de config só em produção (quebraria desenvolvimento)**

---

## 📝 Checklist de Implementação

### Configurações
- [ ] Alterar CACHE_STORE para file
- [ ] Alterar SESSION_DRIVER para file
- [ ] Configurar DB_LOG_SLOW_QUERIES
- [ ] Rodar comandos de cache em produção

### Models
- [ ] Adicionar $with em Product
- [ ] Adicionar $with em ProductInventory
- [ ] Adicionar $with em InventoryMovement

### Database
- [ ] Criar e rodar migration de índices
- [ ] Verificar slow query log
- [ ] Otimizar queries grandes

### Controllers
- [ ] Adicionar eager loading em InventoryController
- [ ] Adicionar eager loading em ProductController
- [ ] Revisar outros controllers

### Produção
- [ ] php artisan config:cache
- [ ] php artisan route:cache
- [ ] php artisan view:cache
- [ ] php artisan event:cache
- [ ] Monitorar performance no Telescope

---

**Última atualização:** 27/11/2025
**Prioridade:** ALTA para Fase 1 e 2
