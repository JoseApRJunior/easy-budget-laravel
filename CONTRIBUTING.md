# Contribuindo para o Easy Budget Laravel

Obrigado por considerar contribuir com o Easy Budget Laravel! Sua participação é muito bem-vinda e valorizada.

## 📋 Como Contribuir

### 1. Reportando Bugs

Encontrou um bug? Por favor, abra uma issue no GitHub com:

-  Descrição detalhada do problema
-  Passos para reproduzir
-  Comportamento esperado vs. atual
-  Informações do ambiente (PHP, Laravel, SO)

### 2. Sugerindo Melhorias

Tem uma ideia para melhorar o projeto? Siga estes passos:

1. Abra uma issue descrevendo sua proposta
2. Explique o problema que resolve
3. Descreva a solução proposta
4. Aguarde feedback da equipe

### 3. Contribuindo com Código

1. **Fork o projeto**
2. **Crie uma branch** para sua feature (`git checkout -b feature/AmazingFeature`)
3. **Commit suas mudanças** (`git commit -m 'Add some AmazingFeature'`)
4. **Push para a branch** (`git push origin feature/AmazingFeature`)
5. **Abra um Pull Request**

## 🏗️ Convenções de Desenvolvimento

### Convenções de Migração

Este projeto adota a **Convenção A (Padrões Laravel)** para nomenclatura de foreign keys e índices nas migrações de banco de dados.

#### Justificativa da Escolha

-  **Simplicidade**: Segue os padrões nativos do Laravel, facilitando a compreensão
-  **Consistência**: Mantém uniformidade com o ecossistema Laravel
-  **Menos propensa a erros**: Reduz a chance de conflitos de nomenclatura
-  **Facilita manutenção**: Padrões bem estabelecidos e documentados

#### Regras de Nomenclatura

##### Foreign Keys

**Padrão**: `fk_{tabela}_{coluna}`

```php
// ✅ Correto - Convenção A (Laravel)
$table->foreign('user_id', 'fk_user_roles_user_id')
    ->references('id')
    ->on('users')
    ->onDelete('cascade');

// ✅ Correto - Convenção A (Laravel)
$table->foreign('role_id', 'fk_user_roles_role_id')
    ->references('id')
    ->on('roles')
    ->onDelete('cascade');

// ✅ Correto - Convenção A (Laravel)
$table->foreign('tenant_id', 'fk_user_roles_tenant_id')
    ->references('id')
    ->on('tenants')
    ->onDelete('cascade');
```

##### Índices

**Padrão**: Laravel gera automaticamente nomes descritivos

```php
// ✅ Correto - Laravel gera: idx_tabela_coluna
$table->index('user_confirmation_token_id');

// ✅ Correto - Índice único
$table->unique('email', 'idx_users_email_unique');

// ✅ Correto - Índice composto
$table->index(['tenant_id', 'user_id'], 'idx_budgets_tenant_user');
```

#### Como Aplicar em Novas Migrações

1. **Sempre use nomes explícitos para foreign keys**:

   ```php
   // ❌ Incorreto - Laravel gera nome automaticamente
   $table->foreign('user_id')->references('id')->on('users');

   // ✅ Correto - Nome explícito
   $table->foreign('user_id', 'fk_user_roles_user_id')->references('id')->on('users');
   ```

2. **Use foreign keys apenas quando necessário**:

   ```php
   // ✅ Correto - Apenas quando há relacionamento real
   $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

   // ❌ Incorreto - Não criar foreign keys desnecessárias
   $table->foreign('status')->references('id')->on('statuses');
   ```

3. **Mantenha consistência na ordem de operações**:

   ```php
   // ✅ Correto - Ordem recomendada
   Schema::table('tabela', function (Blueprint $table) {
       // 1. Adicionar colunas
       $table->unsignedBigInteger('nova_coluna')->nullable();

       // 2. Adicionar foreign keys
       $table->foreign('nova_coluna', 'fk_tabela_nova_coluna')
           ->references('id')
           ->on('tabela_referencia');

       // 3. Adicionar índices
       $table->index('nova_coluna');
   });
   ```

#### Exemplos Práticos

##### Exemplo 1: Relacionamento Many-to-One

```php
Schema::table('budgets', function (Blueprint $table) {
    $table->unsignedBigInteger('user_confirmation_token_id')->nullable();

    // Foreign key com nome explícito
    $table->foreign('user_confirmation_token_id', 'fk_budgets_user_confirmation_token_id')
        ->references('id')
        ->on('user_confirmation_tokens')
        ->onDelete('set null');

    // Índice para performance
    $table->index('user_confirmation_token_id');
});
```

##### Exemplo 2: Relacionamento Many-to-Many

```php
Schema::table('user_roles', function (Blueprint $table) {
    // Foreign keys com nomes explícitos
    $table->foreign('user_id', 'fk_user_roles_user_id')
        ->references('id')
        ->on('users')
        ->onDelete('cascade');

    $table->foreign('role_id', 'fk_user_roles_role_id')
        ->references('id')
        ->on('roles')
        ->onDelete('cascade');

    $table->foreign('tenant_id', 'fk_user_roles_tenant_id')
        ->references('id')
        ->on('tenants')
        ->onDelete('cascade');
});
```

##### Exemplo 3: Índices Compostos

```php
Schema::table('reports', function (Blueprint $table) {
    // Índice composto para consultas otimizadas
    $table->index(['tenant_id', 'created_at'], 'idx_reports_tenant_created_at');

    // Índice único para evitar duplicatas
    $table->unique(['tenant_id', 'name'], 'idx_reports_tenant_name_unique');
});
```

## 🧪 Testes

-  Execute os testes antes de submeter PR: `php artisan test`
-  Mantenha cobertura de testes adequada
-  Teste cenários de sucesso e falha

## 📝 Documentação

-  Mantenha a documentação atualizada
-  Use comentários claros no código
-  Documente APIs e endpoints

## 🔒 Segurança

-  Nunca commite credenciais
-  Use prepared statements
-  Valide inputs adequadamente
-  Mantenha dependências atualizadas

## 📞 Suporte

Se precisar de ajuda, sinta-se à vontade para:

-  Abrir uma issue no GitHub
-  Participar das discussões
-  Contribuir com melhorias

---

**Obrigado por contribuir!** 🚀
