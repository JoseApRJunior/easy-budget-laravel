# Legacy Assets Backup

Este documento contém o backup dos assets CSS e JS que foram removidos da pasta `public/` após a migração para Vite.

## Data da Migração
**Data:** 24 de Janeiro de 2025
**Motivo:** Migração completa para Vite como bundler de assets

## Arquivos CSS Removidos da pasta `public/css/`

### alerts.css
```css
/* Conteúdo será adicionado aqui */
```

### layout.css  
```css
/* Conteúdo será adicionado aqui */
```

### navigation-improvements.css
```css
/* Conteúdo será adicionado aqui */
```

### variables.css
```css
/* Conteúdo será adicionado aqui */
```

## Arquivos JS Removidos da pasta `public/js/`

### Lista de arquivos JavaScript que foram migrados:
- alert-demo.js
- budget.js
- budget_create.js
- budget_report.js
- budget_update.js
- change_password.js
- customer.js
- customer_create.js
- home.js
- invoice.js
- login.js
- main.js
- monitoring.js
- product.js
- product_create.js
- product_update.js
- provider_update.js
- service.js
- service_create.js
- service_update.js
- settings.js

## Nova Estrutura com Vite

Os assets agora estão organizados em:
- `resources/css/` - Arquivos CSS fonte
- `resources/js/` - Arquivos JavaScript fonte
- `public/build/` - Assets compilados pelo Vite (gerados automaticamente)

## Como Restaurar (se necessário)

Se por algum motivo precisar restaurar os assets antigos:

1. Copie os arquivos deste backup para `public/css/` e `public/js/`
2. Reverta as alterações nas views para usar `asset()` em vez de `@vite()`
3. Desabilite o Vite no `vite.config.js`

## Comandos Vite

- **Desenvolvimento:** `npm run dev`
- **Produção:** `npm run build`
- **Preview:** `npm run preview`