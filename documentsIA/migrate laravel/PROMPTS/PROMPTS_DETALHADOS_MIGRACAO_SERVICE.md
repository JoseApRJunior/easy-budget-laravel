## 📝 Prompts Detalhados para Implementação

### **Prompt 1: ServiceController**

```
Implemente app/Http/Controllers/ServiceController.php seguindo:
- Injeção de ServiceService
- Métodos CRUD completos (index, create, store, show, edit, update, destroy)
- Métodos especiais (activate, duplicate)
- Form Requests para validação
- Tratamento de ServiceResult
- Responses padronizadas (views/redirects/JSON)
- Error handling robusto
- Flash messages consistentes
```

### **Prompt 2: ServiceService**

```
Crie app/Services/Domain/ServiceService.php com:
- Extensão de AbstractBaseService
- Lógica de negócio completa
- Transações DB para operações complexas
- Validações de regras de negócio
- Cálculos automáticos (preços, margens)
- Geração de códigos únicos
- Métodos: createService, updateService, activateService, duplicateService
- ServiceResult para retornos padronizados
```

### **Prompt 3: ServiceRepository**

```
Desenvolva app/Repositories/ServiceRepository.php:
- Extensão de AbstractTenantRepository
- Queries com filtros avançados (getFiltered)
- Eager loading para relacionamentos
- Scoping automático por tenant
- Métodos para métricas (countByStatus, getAveragePrice)
- Validação de unicidade (codeExists)
- Paginação automática
```

### **Prompt 4: Service Model**

```
Implemente app/Models/Service.php com:
- Traits: HasFactory, SoftDeletes, BelongsToTenant
- Relacionamentos: category, items, budgetItems, tenant
- Casts: ServiceStatus enum, decimais para preços
- Scopes: active, byCategory
- Accessors: getFormattedPriceAttribute
- Métodos de negócio: isActive, canBeDeleted
- Fillable e hidden apropriados
```

### **Prompt 5: Form Requests**

```
Crie app/Http/Requests/ServiceStoreRequest.php e ServiceUpdateRequest.php:
- Validações robustas para todos os campos
- Rules para unicidade de código por tenant
- Validação de relacionamentos (category_id exists)
- Mensagens customizadas em português
- prepareForValidation para formatação de dados
- Validação de array de itens de serviço
```

### **Prompt 6: ServiceStatus Enum**

```
Implemente app/Enums/ServiceStatus.php:
- Cases: ACTIVE, INACTIVE, DRAFT
- Métodos: label(), color(), icon()
- Implementar BackedEnum com string values
- Métodos estáticos para listagem
```

### **Prompt 7: Views**

```
Crie views em resources/views/pages/services/:
- index.blade.php: Lista com filtros e paginação
- create.blade.php: Formulário de criação
- edit.blade.php: Formulário de edição
- show.blade.php: Detalhes do serviço
- Usar padrões do projeto (Bootstrap 5.3, Alpine.js)
- JavaScript para interações dinâmicas
- Modais de confirmação
```

### **Prompt 8: Migrations**

```
Crie migrations para:
- create_services_table.php
- create_service_items_table.php
- create_service_categories_table.php
- Campos obrigatórios: tenant_id, name, code, price, status
- Índices: tenant_id, code único por tenant
- Foreign keys com cascade
```

### **Prompt 9: Factories e Seeders**

```
Implemente:
- ServiceFactory.php com dados realistas
- ServiceCategoryFactory.php
- ServiceSeeder.php para dados iniciais
- Relacionamentos corretos com tenant
```

### **Prompt 10: Testes**

```
Crie testes em tests/:
- Feature/ServiceControllerTest.php: Todos os endpoints
- Unit/ServiceServiceTest.php: Lógica de negócio
- Unit/ServiceRepositoryTest.php: Queries
- Usar DatabaseTransactions
- Factories para dados de teste
- Assertions robustas
```

---

## 🎯 **Ordem de Implementação Recomendada**

1. **ServiceStatus Enum** (base)
2. **Service Model** (entidade principal)
3. **Migrations** (estrutura de dados)
4. **ServiceRepository** (acesso a dados)
5. **ServiceService** (lógica de negócio)
6. **Form Requests** (validação)
7. **ServiceController** (HTTP layer)
8. **Views** (interface)
9. **Factories/Seeders** (dados de teste)
10.   **Testes** (validação)
