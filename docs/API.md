# Documentação da API

## Visão Geral

Esta documentação descreve as APIs RESTful implementadas no sistema Easy Budget Laravel.

Base URL: `http://localhost/api`

## Autenticação

A maioria dos endpoints requer autenticação via token Bearer:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

## Controllers Implementados

### PlanController

#### Listar Planos

```http
GET /plans
```

**Resposta:**

```json
{
   "data": [
      {
         "id": 1,
         "name": "Plano Básico",
         "description": "Plano para pequenas empresas",
         "is_active": true,
         "created_at": "2025-01-01T00:00:00.000000Z"
      }
   ]
}
```

#### Criar Plano

```http
POST /plans
Content-Type: application/json

{
  "name": "Plano Premium",
  "description": "Plano para empresas grandes",
  "is_active": true
}
```

#### Ver Plano Específico

```http
GET /plans/{id}
```

#### Atualizar Plano

```http
PUT /plans/{id}
Content-Type: application/json

{
  "name": "Plano Premium Atualizado",
  "description": "Descrição atualizada",
  "is_active": false
}
```

#### Excluir Plano

```http
DELETE /plans/{id}
```

### UserController

#### Listar Usuários

```http
GET /users
```

**Query Parameters:**

-  `per_page`: Número de usuários por página (padrão: 15)
-  `page`: Página atual
-  `search`: Termo de busca

#### Criar Usuário

```http
POST /users
Content-Type: application/json

{
  "name": "João Silva",
  "email": "joao@exemplo.com",
  "password": "senha123",
  "password_confirmation": "senha123"
}
```

#### Ativar Usuário

```http
PATCH /users/{id}/activate
```

### BudgetController

#### Listar Orçamentos

```http
GET /budgets
```

**Query Parameters:**

-  `status`: Filtrar por status
-  `plan_id`: Filtrar por plano
-  `user_id`: Filtrar por usuário

**Resposta:**

```json
{
   "data": [
      {
         "id": 1,
         "title": "Orçamento Web Site",
         "status": "draft",
         "plan_id": 1,
         "user_id": 1,
         "total": 1500.0,
         "created_at": "2025-01-01T00:00:00.000000Z"
      }
   ]
}
```

#### Criar Orçamento

```http
POST /budgets
Content-Type: application/json

{
  "title": "Novo Orçamento",
  "description": "Descrição do orçamento",
  "plan_id": 1,
  "items": [
    {
      "name": "Desenvolvimento",
      "description": "Serviços de desenvolvimento",
      "quantity": 1,
      "unit_price": 1000.00
    }
  ]
}
```

#### Alterar Status do Orçamento

```http
PATCH /budgets/{id}/status
Content-Type: application/json

{
  "status": "approved"
}
```

## Códigos de Status HTTP

| Código | Descrição                |
| ------ | ------------------------ |
| 200    | OK                       |
| 201    | Criado com sucesso       |
| 400    | Dados inválidos          |
| 401    | Não autorizado           |
| 403    | Proibido                 |
| 404    | Não encontrado           |
| 422    | Entidade não processável |
| 500    | Erro interno do servidor |

## Tratamento de Erros

### Formato de Erro

```json
{
   "message": "Mensagem de erro",
   "errors": {
      "field": ["Erro específico do campo"]
   }
}
```

### Exemplo de Erro de Validação

```json
{
   "message": "The given data was invalid.",
   "errors": {
      "email": ["O campo email é obrigatório."],
      "password": ["O campo password deve ter pelo menos 8 caracteres."]
   }
}
```

## Rate Limiting

A API implementa rate limiting para prevenir abuso:

-  Limite: 60 requests por minuto por usuário
-  Header: `X-RateLimit-Limit`, `X-RateLimit-Remaining`

## Versionamento

A API utiliza versionamento na URL:

-  `/api/v1/plans`
-  `/api/v1/users`
-  `/api/v1/budgets`
