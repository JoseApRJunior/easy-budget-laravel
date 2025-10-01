# 🎨 Design System - Easy Budget Laravel

## 📋 Visão Geral

Sistema de design consistente baseado em **TailwindCSS** com componentes reutilizáveis em **Laravel Blade**, otimizado para a experiência do usuário corporativo.

---

## 🏗️ Princípios de Design

### **Clareza**

-  Interface limpa e intuitiva
-  Hierarquia visual clara
-  Feedback imediato ao usuário

### **Consistência**

-  Padrões visuais uniformes
-  Comportamentos previsíveis
-  Componentes reutilizáveis

### **Acessibilidade**

-  WCAG 2.1 AA compliance
-  Navegação por teclado
-  Contraste adequado

### **Performance**

-  Carregamento rápido
-  Otimizações de assets
-  Design mobile-first

---

## 🎯 Paleta de Cores

### Cores Primárias

```css
/* Azul Corporativo */
--color-primary-50: #eff6ff;
--color-primary-100: #dbeafe;
--color-primary-500: #3b82f6;
--color-primary-600: #2563eb;
--color-primary-700: #1d4ed8;

/* Verde Sucesso */
--color-success-50: #ecfdf5;
--color-success-500: #10b981;
--color-success-600: #059669;

/* Vermelho Erro */
--color-danger-50: #fef2f2;
--color-danger-500: #ef4444;
--color-danger-600: #dc2626;

/* Amarelo Aviso */
--color-warning-50: #fffbeb;
--color-warning-500: #f59e0b;
--color-warning-600: #d97706;

/* Azul Info */
--color-info-50: #eff6ff;
--color-info-500: #3b82f6;
--color-info-600: #2563eb;
```

### Cores de Superfície

```css
/* Fundo */
--color-gray-50: #f9fafb;
--color-gray-100: #f3f4f6;
--color-gray-200: #e5e7eb;

/* Texto */
--color-gray-600: #4b5563;
--color-gray-700: #374151;
--color-gray-800: #1f2937;
--color-gray-900: #111827;

/* Bordas */
--color-gray-300: #d1d5db;
--color-gray-400: #9ca3af;
```

### Estados Interativos

```css
/* Hover */
--color-blue-50: #eff6ff; (fundo hover)

/* Focus */
--color-blue-500: #3b82f6; (borda focus)

/* Active */
--color-blue-600: #2563eb; (fundo active)

/* Disabled */
--color-gray-100: #f3f4f6; (fundo disabled)
--color-gray-400: #9ca3af; (texto disabled)
```

---

## 📝 Tipografia

### Fonte Principal

```css
font-family: "Inter", system-ui, -apple-system, sans-serif;
```

### Escala Tipográfica

| Classe      | Tamanho | Altura | Uso              |
| ----------- | ------- | ------ | ---------------- |
| `text-xs`   | 12px    | 16px   | Labels pequenas  |
| `text-sm`   | 14px    | 20px   | Corpo do texto   |
| `text-base` | 16px    | 24px   | Texto padrão     |
| `text-lg`   | 18px    | 28px   | Subtítulos       |
| `text-xl`   | 20px    | 28px   | Títulos pequenos |
| `text-2xl`  | 24px    | 32px   | Títulos médios   |
| `text-3xl`  | 30px    | 36px   | Títulos grandes  |

### Pesos de Fonte

-  **400 (Regular)**: Texto normal
-  **500 (Medium)**: Ênfase sutil
-  **600 (Semibold)**: Títulos e destaques
-  **700 (Bold)**: Chamadas importantes

### Hierarquia Visual

```html
<h1 class="text-3xl font-bold text-gray-900">Título Principal</h1>
<h2 class="text-2xl font-semibold text-gray-800">Subtítulo</h2>
<h3 class="text-xl font-medium text-gray-700">Seção</h3>
<p class="text-base text-gray-600">Texto normal</p>
<small class="text-sm text-gray-500">Texto secundário</small>
```

---

## 📐 Espaçamento

### Sistema de Espaçamento

```css
/* Base: 0.25rem (4px) */
--spacing-1: 0.25rem; /* 4px */
--spacing-2: 0.5rem; /* 8px */
--spacing-3: 0.75rem; /* 12px */
--spacing-4: 1rem; /* 16px */
--spacing-5: 1.25rem; /* 20px */
--spacing-6: 1.5rem; /* 24px */
--spacing-8: 2rem; /* 32px */
--spacing-10: 2.5rem; /* 40px */
--spacing-12: 3rem; /* 48px */
--spacing-16: 4rem; /* 64px */
--spacing-20: 5rem; /* 80px */
--spacing-24: 6rem; /* 96px */
```

### Aplicação Prática

```html
<!-- Entre elementos -->
<div class="mb-4">...</div>
<!-- 16px -->
<div class="mb-6">...</div>
<!-- 24px -->
<div class="mb-8">...</div>
<!-- 32px -->

<!-- Padding interno -->
<div class="p-4">...</div>
<!-- 16px interno -->
<div class="p-6">...</div>
<!-- 24px interno -->
<div class="p-8">...</div>
<!-- 32px interno -->

<!-- Gap em grids -->
<div class="grid grid-cols-2 gap-4">...</div>
<!-- 16px gap -->
<div class="grid grid-cols-2 gap-6">...</div>
<!-- 24px gap -->
```

---

## 🧩 Componentes Base

### Botões

#### Variantes

```html
<!-- Primário -->
<x-ui.button variant="primary">Primário</x-ui.button>

<!-- Secundário -->
<x-ui.button variant="secondary">Secundário</x-ui.button>

<!-- Sucesso -->
<x-ui.button variant="success">Sucesso</x-ui.button>

<!-- Perigo -->
<x-ui.button variant="danger">Perigo</x-ui.button>

<!-- Aviso -->
<x-ui.button variant="warning">Aviso</x-ui.button>

<!-- Info -->
<x-ui.button variant="info">Info</x-ui.button>

<!-- Outline -->
<x-ui.button variant="outline-primary">Outline</x-ui.button>

<!-- Ghost -->
<x-ui.button variant="ghost">Ghost</x-ui.button>

<!-- Link -->
<x-ui.button variant="link">Link</x-ui.button>
```

#### Tamanhos

```html
<x-ui.button size="xs">XS</x-ui.button>
<x-ui.button size="sm">Pequeno</x-ui.button>
<x-ui.button size="md">Médio</x-ui.button>
<x-ui.button size="lg">Grande</x-ui.button>
<x-ui.button size="xl">XL</x-ui.button>
```

#### Estados

```html
<!-- Loading -->
<x-ui.button loading>Carregando...</x-ui.button>

<!-- Disabled -->
<x-ui.button disabled>Desabilitado</x-ui.button>

<!-- Link -->
<x-ui.button href="/dashboard">Link</x-ui.button>
```

### Cards

```html
<!-- Básico -->
<x-ui.card>
   <p>Conteúdo do card</p>
</x-ui.card>

<!-- Com header -->
<x-ui.card header="Título do Card">
   <p>Conteúdo do card</p>
</x-ui.card>

<!-- Com header e footer -->
<x-ui.card header="Título" footer="<x-ui.button>Action</x-ui.button>">
   <p>Conteúdo do card</p>
</x-ui.card>

<!-- Variações de padding -->
<x-ui.card padding="sm">...</x-ui.card>
<x-ui.card padding="lg">...</x-ui.card>

<!-- Variações de shadow -->
<x-ui.card shadow="none">...</x-ui.card>
<x-ui.card shadow="md">...</x-ui.card>
<x-ui.card shadow="lg">...</x-ui.card>
```

### Badges

```html
<!-- Tipos -->
<x-ui.badge type="primary">Primário</x-ui.badge>
<x-ui.badge type="success">Sucesso</x-ui.badge>
<x-ui.badge type="danger">Erro</x-ui.badge>
<x-ui.badge type="warning">Aviso</x-ui.badge>
<x-ui.badge type="info">Info</x-ui.badge>

<!-- Tamanhos -->
<x-ui.badge size="xs">XS</x-ui.badge>
<x-ui.badge size="sm">Pequeno</x-ui.badge>
<x-ui.badge size="md">Médio</x-ui.badge>
<x-ui.badge size="lg">Grande</x-ui.badge>

<!-- Com dot -->
<x-ui.badge type="success" dot>Online</x-ui.badge>
```

### Formulários

#### Input

```html
<x-form.input label="Nome" name="name" placeholder="Digite seu nome" required />
```

#### Select

```html
<x-form.select
   label="Estado"
   name="state"
   :options="[
        ['value' => 'SP', 'label' => 'São Paulo'],
        ['value' => 'RJ', 'label' => 'Rio de Janeiro'],
    ]"
   placeholder="Selecione um estado"
   required
/>
```

#### Textarea

```html
<x-form.textarea
   label="Descrição"
   name="description"
   rows="4"
   placeholder="Digite uma descrição"
   required
/>
```

#### Checkbox

```html
<x-form.checkbox label="Aceito os termos" name="terms" required />
```

---

## 🎭 Estados e Feedback

### Estados de Formulário

```html
<!-- Válido -->
<x-form.input class="border-green-500 focus:ring-green-500" />

<!-- Inválido -->
<x-form.input class="border-red-500 focus:ring-red-500" />

<!-- Disabled -->
<x-form.input disabled class="bg-gray-50 text-gray-500" />

<!-- Readonly -->
<x-form.input readonly class="bg-gray-50" />
```

### Mensagens de Feedback

```html
<!-- Sucesso -->
<x-alert type="success"> Dados salvos com sucesso! </x-alert>

<!-- Erro -->
<x-alert type="error"> Ocorreu um erro ao salvar os dados. </x-alert>

<!-- Aviso -->
<x-alert type="warning"> Verifique os dados antes de continuar. </x-alert>

<!-- Info -->
<x-alert type="info"> Esta ação não pode ser desfeita. </x-alert>
```

---

## 📱 Design Responsivo

### Breakpoints

```css
/* Mobile First */
sm: 640px   /* Small devices */
md: 768px   /* Tablets */
lg: 1024px  /* Laptops */
xl: 1280px  /* Desktops */
2xl: 1536px /* Large screens */
```

### Grid Responsivo

```html
<!-- 1 coluna mobile → 2 colunas tablet → 3 colunas desktop -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
   <div>Item 1</div>
   <div>Item 2</div>
   <div>Item 3</div>
</div>

<!-- Largura máxima com padding lateral -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
   <div>Conteúdo centralizado</div>
</div>
```

### Tipografia Responsiva

```html
<!-- Texto que escala com a tela -->
<h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold">Título Responsivo</h1>

<!-- Espaçamento responsivo -->
<div class="p-4 sm:p-6 lg:p-8">
   <p>Conteúdo com padding responsivo</p>
</div>
```

---

## ♿ Acessibilidade

### Estrutura Semântica

```html
<!-- Use elementos semânticos -->
<header>
   <nav role="navigation" aria-label="Menu principal">
      <ul>
         <li><a href="/dashboard">Dashboard</a></li>
      </ul>
   </nav>
</header>

<main role="main">
   <section aria-labelledby="section-title">
      <h2 id="section-title">Título da Seção</h2>
      <p>Conteúdo da seção</p>
   </section>
</main>
```

### Navegação por Teclado

```html
<!-- Botões devem ser focusable -->
<button class="focus:outline-none focus:ring-2 focus:ring-blue-500">
   Botão Focusable
</button>

<!-- Links devem ter href válido -->
<a href="/dashboard" class="focus:ring-2 focus:ring-blue-500">
   Link Focusable
</a>
```

### ARIA Labels

```html
<!-- Para elementos interativos sem texto visível -->
<button aria-label="Fechar modal" class="focus:ring-2">
   <i class="bi bi-x"></i>
</button>

<!-- Para regiões dinâmicas -->
<div role="alert" aria-live="polite">Mensagem de status</div>
```

---

## 🎨 Padrões Visuais

### Sombras

```css
/* Padrões de sombra */
shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05)
shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)
shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)
shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)
```

### Bordas

```css
/* Espessura */
border: 1px solid
border-2: 2px solid
border-4: 4px solid

/* Estilo */
border-solid: linha sólida
border-dashed: linha tracejada
border-dotted: linha pontilhada

/* Raio */
rounded-none: sem arredondamento
rounded-sm: 0.125rem
rounded: 0.25rem
rounded-md: 0.375rem
rounded-lg: 0.5rem
rounded-xl: 0.75rem
rounded-full: 9999px
```

### Transições

```css
/* Duração */
transition-none: 0s
transition-75: 75ms
transition-100: 100ms
transition-150: 150ms
transition-200: 200ms
transition-300: 300ms

/* Timing */
ease-linear: linear
ease-in: cubic-bezier(0.4, 0, 1, 1)
ease-out: cubic-bezier(0, 0, 0.2, 1)
ease-in-out: cubic-bezier(0.4, 0, 0.2, 1)
```

---

## 📋 Checklist de Implementação

### Para Novos Componentes

-  [ ] Segue paleta de cores definida
-  [ ] Usa escala tipográfica correta
-  [ ] Implementa espaçamento consistente
-  [ ] É responsivo (mobile-first)
-  [ ] Tem estados interativos (hover, focus, active)
-  [ ] É acessível (ARIA, teclado)
-  [ ] Usa componentes base existentes
-  [ ] Está documentado

### Para Novas Páginas

-  [ ] Usa layout apropriado
-  [ ] Implementa navegação consistente
-  [ ] Tem títulos descritivos
-  [ ] Usa componentes padronizados
-  [ ] Trata estados de loading/erro
-  [ ] É responsiva
-  [ ] Tem meta tags adequadas

---

## 🔧 Ferramentas e Recursos

### Desenvolvimento

-  **TailwindCSS**: Framework CSS utilitário
-  **Alpine.js**: Framework JavaScript minimalista
-  **Laravel Blade**: Template engine
-  **Vite**: Build tool e dev server

### Design

-  **Figma**: Design e prototipagem
-  **Storybook**: Documentação de componentes
-  **Lighthouse**: Auditoria de performance
-  **axe-core**: Testes de acessibilidade

### Cores

-  **Coolors**: Paletas de cores
-  **Contrast Checker**: Verificação de contraste
-  **Color Palette Generator**: Geração de paletas

---

## 📚 Referências

-  [TailwindCSS Documentation](https://tailwindcss.com/docs)
-  [Laravel Blade Documentation](https://laravel.com/docs/blade)
-  [Alpine.js Documentation](https://alpinejs.dev/)
-  [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
-  [MDN Web Docs](https://developer.mozilla.org/)

---

**Documento criado em:** 2025-09-30
**Versão:** 1.0
**Status:** ✅ Sistema de Design Documentado
