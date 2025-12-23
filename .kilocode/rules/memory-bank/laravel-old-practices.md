# Laravel Old() Practices - Filtros e Formulários

## 🎯 Uso do `old()` em Filtros de Busca

### **💡 Melhor Prática: Sempre usar `old()` em filtros de formulário**

O método `old()` do Laravel é essencial para manter a experiência do usuário consistente após submissão de formulários, redirects e refreshes de página.

### **🔧 Como aplicar nos filtros**

#### **Select com opções múltiplas:**

```php
<select name="active" id="active" class="form-control">
    <option value="1"
        {{ old('active', $filters['active'] ?? '') === '1' ? 'selected' : '' }}>
        Ativo
    </option>
    <option value="0"
        {{ old('active', $filters['active'] ?? '') === '0' ? 'selected' : '' }}>
        Inativo
    </option>
    <option value=""
        {{ old('active', $filters['active'] ?? '') === '' ? 'selected' : '' }}>
        Todos
    </option>
</select>
```

#### **Select com estados diferentes:**

```php
<select name="deleted" id="deleted" class="form-control">
    <option value="current"
        {{ old('deleted', $filters['deleted'] ?? '') === 'current' ? 'selected' : '' }}>
        Atuais
    </option>
    <option value="only"
        {{ old('deleted', $filters['deleted'] ?? '') === 'only' ? 'selected' : '' }}>
        Deletados
    </option>
    <option value=""
        {{ old('deleted', $filters['deleted'] ?? '') === '' ? 'selected' : '' }}>
        Todos
    </option>
</select>
```

#### **Input text:**

```php
<input type="text" class="form-control" id="search" name="search"
    value="{{ old('search', $filters['search'] ?? '') }}"
    placeholder="Buscar...">
```

### **📊 Fluxo de Funcionamento**

```
1. Usuário seleciona filtro → Formulário enviado
2. Controller processa → Redireciona para mesma página
3. View carrega → old('campo') recupera valor selecionado
4. Campo mantém valor → Usuário vê filtro aplicado
```

### **🎯 Benefícios**

#### **✅ Experiência do Usuário**

-  **Feedback visual** imediato do filtro aplicado
-  **Navegação consistente** - valores persistem após redirects
-  **Menos confusão** - usuário sabe qual filtro está ativo
-  **UX fluida** - sem perda de estado entre páginas

#### **✅ Código Limpo**

-  **Sintaxe clara** e legível
-  **Menos lógica** complexa nos templates
-  **Manutenção fácil** - padrão Laravel consistente
-  **Código reutilizável** - mesmo padrão em todos os filtros

#### **✅ Performance**

-  **Sem consultas extras** ao banco
-  **Processamento rápido** no lado do cliente
-  **Cache eficiente** dos valores do formulário

### **💡 Dicas Adicionais**

#### **Para outros tipos de campos:**

**Checkbox:**

```php
<input type="checkbox" name="remember" {{ old('remember', true) ? 'checked' : '' }}>
```

**Radio:**
 
```php
<input type="radio" name="type" value="category"
    {{ old('type', 'category') === 'category' ? 'checked' : '' }}>
```

**Validação de formulário:**

```php
<input value="{{ old('email') }}"
    class="@error('email') is-invalid @enderror">
@error('email')
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
```

### **🚀 Padrão Recomendado**

#### **Estrutura padrão para selects:**

```php
<select name="campo" id="campo" class="form-control">
    <option value="valor1"
        {{ old('campo', $filters['campo'] ?? '') === 'valor1' ? 'selected' : '' }}>
        Opção 1
    </option>
    <option value="valor2"
        {{ old('campo', $filters['campo'] ?? '') === 'valor2' ? 'selected' : '' }}>
        Opção 2
    </option>
    <option value=""
        {{ old('campo', $filters['campo'] ?? '') === '' ? 'selected' : '' }}>
        Todos
    </option>
</select>
```

### **⚠️ Erros Comuns a Evitar**

#### **❌ Sem old() - Perde estado:**

```php
{{ ($filters['active'] ?? null) === '1' ? 'selected' : '' }}
```

#### **❌ Sintaxe incorreta:**

```php
{{ old('active') === '1' ? 'selected' : '' }}  // Falta fallback
```

#### **✅ Correto - Com old() e fallback:**

```php
{{ old('active', $filters['active'] ?? '') === '1' ? 'selected' : '' }}
```
**Última atualização:** 20/12/2025 - Sugestão de uso de `old()` em filtros de formulário
### **🎯 Conclusão**

**Sempre usar `old()` em filtros de formulário** porque:

1. **É nativo do Laravel** - projetado para esse propósito
2. **Mantém consistência** - padrão usado em todo o framework
3. **Funciona perfeitamente** - solução testada e confiável
4. **Melhora UX** - experiência do usuário mais fluida
5. **Código limpo** - sintaxe clara e manutenível

Esta prática garante que os filtros selecionados permaneçam visíveis após qualquer submissão, refresh ou redirect, proporcionando uma experiência de usuário muito melhor.
