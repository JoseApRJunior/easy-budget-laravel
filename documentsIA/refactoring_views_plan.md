# Plano de Refatoração de Views Blade

## 🎯 Objetivo

Substituir a lógica hardcoded e as implementações remanescentes do Twig nas views Blade por chamadas aos novos `Helpers` centralizados, melhorando a legibilidade, manutenção e aderência às boas práticas do Laravel.

## 헬 Helpers Criados

-  `App\Helpers\StatusHelper`: Funções para lógica de status (badges, transições, cores, etc.).
-  `App\Helpers\DateHelper`: Funções para formatação de datas.
-  `App\Helpers\MathHelper`: Funções para cálculos matemáticos (ex: porcentagem).
-  `App\Helpers\ModelHelper`: Funções para interagir com propriedades de models.

## 🗺️ Etapas da Refatoração

O processo será dividido pelas principais seções da aplicação, focando em substituir a lógica antiga pelos novos helpers.

### 1. Views de Orçamentos (Budgets)

-  **Arquivos Alvo:**
   -  `resources/views/budgets/index.blade.php`
   -  `resources/views/budgets/show.blade.php`
   -  `resources/views/budgets/form.blade.php`
-  **Helpers a Utilizar:**
   -  `StatusHelper` para exibir badges de status, opções de status e verificar permissões de edição.
   -  `DateHelper` para formatar datas.
   -  `MathHelper` para calcular progresso.

### 2. Views de Serviços (Services)

-  **Arquivos Alvo:**
   -  `resources/views/services/index.blade.php`
   -  `resources/views/services/form.blade.php`
-  **Helpers a Utilizar:**
   -  `StatusHelper` para badges e opções de status.
   -  `DateHelper` para formatação de datas.

### 3. Views de Faturas (Invoices)

-  **Arquivos Alvo:**
   -  `resources/views/invoices/index.blade.php`
   -  `resources/views/invoices/show.blade.php`
-  **Helpers a Utilizar:**
   -  `StatusHelper` para badges e opções de status.
   -  `DateHelper` para formatação de datas.

### 4. Views de Backups

-  **Arquivos Alvo:**
   -  `resources/views/backups/index.blade.php`
-  **Helpers a Utilizar:**
   -  `DateHelper` para formatar as datas dos backups e calcular a diferença de tempo.

## 🚀 Processo de Execução

1. **Análise da View:** Ler o conteúdo da view Blade para identificar pontos de refatoração.
2. **Aplicação dos Helpers:** Substituir a lógica existente por chamadas aos métodos estáticos dos helpers.
3. **Validação:** Garantir que a sintaxe do Blade está correta e que as chamadas aos helpers estão adequadas.
4. **Atualização:** Salvar o arquivo da view com as modificações.
