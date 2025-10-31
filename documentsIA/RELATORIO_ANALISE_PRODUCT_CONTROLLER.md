# Relatório de Análise - ProductController (Sistema Antigo)

## 📋 Sumário Executivo

Análise completa do `ProductController` do sistema antigo para migração ao Laravel 12.

**Arquivo:** `old-system/app/controllers/ProductController.php`  
**Data:** 2025  
**Objetivo:** Mapear funcionalidades, dependências e fluxos para implementação no novo sistema.

---

## 🎯 Visão Geral

### Dependências Injetadas (6 total)

1. **Twig** - Template engine
2. **Sanitize** - Sanitização
3. **Product** - Model de produtos
4. **UploadImage** - Upload e processamento de imagens
5. **ActivityService** - Logs
6. **Request** - HTTP Request

---

## 📊 Métodos (9 total)

### 1. `index()` - Lista de Produtos
- **Rota:** GET `/provider/products`
- **View:** `pages/product/index.twig`
- **Função:** Exibe listagem de produtos

### 2. `create()` - Formulário de Criação
- **Rota:** GET `/provider/products/create`
- **View:** `pages/product/create.twig`
- **Função:** Formulário para cadastrar novo produto

### 3. `store()` - Criar Produto
- **Rota:** POST `/provider/products`
- **Validação:** `ProductFormRequest::validate()`
- **Lógica:**
  1. Valida dados do formulário
  2. Gera código único: `PROD000001`
  3. Converte preço: `convertMoneyToFloat()`
  4. Processa upload de imagem (se fornecida)
  5. Redimensiona imagem: 200px largura
  6. Cria ProductEntity
  7. Salva no banco
  8. Registra atividade: `product_created`
- **Redirect:** `/provider/products` (sucesso)

### 4. `update($code)` - Formulário de Edição
- **Rota:** GET `/provider/products/update/{code}`
- **View:** `pages/product/update.twig`
- **Dados:** Produto por código

### 5. `update_store()` - Atualizar Produto
- **Rota:** POST `/provider/products/update`
- **Validação:** `ProductFormRequest::validate()`
- **Lógica:**
  1. Valida dados
  2. Busca produto existente
  3. Converte preço
  4. Processa imagem:
     - Nova imagem: upload + deleta antiga
     - Remove imagem: deleta física + null no banco
     - Sem mudança: mantém atual
  5. Compara dados originais vs novos
  6. Atualiza apenas se houver mudanças
  7. Registra atividade: `product_updated` (before/after)
- **Redirect:** `/provider/products` (sucesso)

### 6. `show($code)` - Detalhes do Produto
- **Rota:** GET `/provider/products/show/{code}`
- **View:** `pages/product/show.twig`
- **Dados:** Produto com inventário via `getProductsWhithInventoryByCode()`

### 7. `deactivate($code)` - Desativar Produto
- **Rota:** POST `/provider/products/deactivate/{code}`
- **Lógica:**
  1. Busca produto por código
  2. Verifica relacionamentos (ignora inventory_movements e product_inventory)
  3. Se houver service_items: impede desativação
  4. Atualiza active = false
  5. Registra atividade: `product_updated`
- **Redirect:** `/provider/products/show/{code}`

### 8. `activate($code)` - Ativar Produto
- **Rota:** POST `/provider/products/activate/{code}`
- **Lógica:**
  1. Busca produto por código
  2. Atualiza active = true
  3. Registra atividade: `product_updated`
- **Redirect:** `/provider/products/show/{code}`

### 9. `delete_store($code)` - Deletar Produto
- **Rota:** POST `/provider/products/delete/{code}`
- **Lógica:**
  1. Busca produto por código
  2. Verifica relacionamentos (ignora inventory_movements e product_inventory)
  3. Se houver service_items: impede exclusão
  4. Deleta produto
  5. Registra atividade: `product_deleted`
- **Redirect:** `/provider/products`

---

## 📦 Estrutura de Dados

### ProductEntity (Campos)
```
id, tenant_id, code, name, description, price, image,
active, created_at, updated_at
```

### Código Único
```php
// Formato: PROD000001
$last_code = $this->product->getLastCode($tenant_id);
if ($last_code instanceof EntityNotFound) {
    $code = 'PROD000001';
} else {
    $number = (int)substr($last_code->code, 4) + 1;
    $code = 'PROD' . str_pad((string)$number, 6, '0', STR_PAD_LEFT);
}
```

---

## 🔄 Fluxos de Negócio

### Fluxo 1: Criação de Produto
1. Provider acessa formulário
2. Preenche dados (nome, descrição, preço)
3. Opcionalmente: faz upload de imagem
4. Sistema gera código único
5. Sistema processa imagem (redimensiona 200px)
6. Sistema salva produto
7. Registra atividade

### Fluxo 2: Atualização de Produto
1. Provider acessa formulário de edição
2. Modifica dados
3. Opções de imagem:
   - Upload nova: substitui antiga
   - Marca "remover": deleta imagem
   - Sem mudança: mantém atual
4. Sistema compara dados
5. Atualiza apenas se houver mudanças
6. Registra atividade com before/after

### Fluxo 3: Desativação de Produto
1. Provider clica em "Desativar"
2. Sistema verifica relacionamentos
3. Se usado em service_items: impede
4. Se não: marca active = false
5. Produto não aparece em listagens ativas
6. Registra atividade

### Fluxo 4: Exclusão de Produto
1. Provider solicita exclusão
2. Sistema verifica relacionamentos
3. Se usado em service_items: impede
4. Se não: deleta produto
5. Deleta imagem física (se existir)
6. Registra atividade

---

## 🖼️ Sistema de Upload de Imagens

### UploadImage (Funcionalidades)
```php
$this->uploadImage->make('image')
    ->resize(200, null, true) // 200px largura, altura proporcional
    ->execute();
$info = $this->uploadImage->get_image_info();
$data['image'] = $info['path']; // Caminho relativo
```

### Gestão de Imagens
- **Upload:** Redimensiona para 200px largura
- **Atualização:** Deleta imagem antiga ao fazer upload de nova
- **Remoção:** Checkbox "remove_image" deleta física + null no banco
- **Exclusão:** Deleta imagem física ao deletar produto

### Armazenamento
```php
// Caminho físico
PUBLIC_PATH . $originalData['image']

// Exemplo: /public/uploads/products/produto123.jpg
```

---

## ⚠️ Pontos Críticos

### 1. Geração de Código Único
```php
$last_code = $this->product->getLastCode($tenant_id);
$number = (int)substr($last_code->code, 4) + 1;
$code = 'PROD' . str_pad((string)$number, 6, '0', STR_PAD_LEFT);
```
**Formato:** PROD000001, PROD000002, ...  
**Ação:** Implementar com lock para evitar duplicatas

### 2. Conversão de Preço
```php
$data['price'] = convertMoneyToFloat($data['price']);
```
**Função:** Converte formato brasileiro (1.234,56) para float (1234.56)

### 3. Verificação de Relacionamentos
- Ignora: `inventory_movements`, `product_inventory`
- Verifica: `service_items`
- Se produto usado em serviços: impede desativação/exclusão

### 4. Soft Delete via Active
- Não deleta fisicamente por padrão
- Usa flag `active` para desativar
- Permite reativação posterior

### 5. Gestão de Imagens
- Deleta imagem antiga ao fazer upload de nova
- Deleta imagem física ao remover ou deletar produto
- Redimensiona automaticamente para 200px

---

## 🐛 Bugs Identificados

### 1. Erro de Sintaxe no activityLogger
**Localização:** `deactivate()` e `activate()` linhas ~280 e ~320
```php
// ERRADO
$$product->id

// CORRETO
$product->id
```
**Impacto:** Erro fatal ao desativar/ativar produto

---

## 📝 Validações (ProductFormRequest)

### Campos Obrigatórios
- name (nome do produto)
- description (descrição)
- price (preço)

### Campos Opcionais
- image (imagem do produto)
- active (status ativo/inativo)

---

## 📝 Recomendações Laravel

### Models
```php
Product (belongsTo: Tenant)
Product (hasMany: ServiceItem, ProductInventory, InventoryMovement)
```

### Controllers
```php
ProductController (provider - CRUD completo)
```

### Services
```php
ProductService - Lógica de negócio
ProductCodeGeneratorService - Códigos únicos
ProductImageService - Gestão de imagens
```

### Form Requests
```php
ProductStoreRequest
ProductUpdateRequest
```

### Events & Listeners
```php
ProductCreated → SendProductCreatedNotification
ProductUpdated → SendProductUpdatedNotification
ProductDeactivated → SendProductDeactivatedNotification
ProductDeleted → SendProductDeletedNotification
```

### Policies
```php
ProductPolicy:
- view, create, update, delete, activate, deactivate
```

### Storage
```php
// Laravel Storage
Storage::disk('public')->put('products/' . $filename, $file);

// Estrutura sugerida
storage/app/public/products/{tenant_id}/{filename}
```

---

## 🔄 Migração para Laravel

### Opção 1: Manter Estrutura Atual
- Tabela products simples
- Flag active para soft delete
- Imagens em storage/public

### Opção 2: Melhorias Sugeridas
- Adicionar soft deletes do Laravel
- Separar produtos de serviços
- Adicionar categorias
- Adicionar unidades de medida
- Múltiplas imagens por produto

### Estrutura Sugerida
```php
products:
- id, tenant_id, code, name, description
- price, cost_price, category_id, unit_id
- active, featured, stock_control
- image, images (JSON)
- deleted_at, created_at, updated_at

product_categories:
- id, tenant_id, name, slug, parent_id

product_units:
- id, name, abbreviation (un, kg, m, etc)
```

---

## ✅ Checklist de Implementação

- [ ] Criar migration de products
- [ ] Criar model Product com relationships
- [ ] Criar ProductService
- [ ] Criar ProductController
- [ ] Implementar geração de código único
- [ ] Implementar upload de imagens
- [ ] Implementar redimensionamento
- [ ] Implementar soft delete
- [ ] Implementar ativação/desativação
- [ ] Implementar verificação de relacionamentos
- [ ] Criar Form Requests
- [ ] Criar Events & Listeners
- [ ] Criar Policies
- [ ] Criar views Blade
- [ ] Implementar testes
- [ ] Corrigir bug do $$product->id

---

## 🔧 Melhorias Identificadas

### 1. Categorização
**Problema:** Produtos sem categorias
**Solução:** Adicionar sistema de categorias

### 2. Unidades de Medida
**Problema:** Sem controle de unidades
**Solução:** Adicionar tabela de unidades (un, kg, m, etc)

### 3. Múltiplas Imagens
**Problema:** Apenas 1 imagem por produto
**Solução:** Permitir galeria de imagens

### 4. Controle de Estoque
**Problema:** Sem integração direta com estoque
**Solução:** Adicionar campos de controle

### 5. Preço de Custo
**Problema:** Apenas preço de venda
**Solução:** Adicionar cost_price para cálculo de margem

---

**Fim do Relatório**
