# Tarefas & Notas

## Banco de Dados

-  [ ] Migrar tabelas de status para enums

## Testes de Salvamento de Dados

### Passos para Testes:

1. **Dados Válidos**

   -  Insira dados válidos em todos os campos obrigatórios e opcionais
   -  Submeta o formulário
   -  Confirme se os dados persistem após recarregar a página

2. **Dados Inválidos**

   -  Teste com campos vazios
   -  Teste com formatos incorretos
   -  Verifique mensagens de erro adequadas

3. **Compatibilidade**

   -  Teste em diferentes navegadores (Chrome, Firefox, Safari, Edge)
   -  Teste em diferentes dispositivos (desktop, tablet, mobile)

4. **Integração**
   -  Verifique se alterações são refletidas em outras partes do sistema

### Resultados dos Testes

| Teste               | Status | Observações |
| ------------------- | ------ | ----------- |
| Dados válidos       | [ ]    |             |
| Campos vazios       | [ ]    |             |
| Formatos incorretos | [ ]    |             |
| Chrome              | [ ]    |             |
| Firefox             | [ ]    |             |
| Safari              | [ ]    |             |
| Edge                | [ ]    |             |
| Desktop             | [ ]    |             |
| Tablet              | [ ]    |             |
| Mobile              | [ ]    |             |
| Integração          | [ ]    |             |

### Falhas Encontradas

[Descreva quaisquer falhas encontradas durante os testes]

##

Realize testes abrangentes na página https://dev.easybudget.net.br/provider/business/edit para verificar se os dados estão sendo salvos corretamente. Inclua os seguintes passos: 1) Insira dados válidos em todos os campos obrigatórios e opcionais; 2) Submeta o formulário e confirme se os dados persistem após recarregar a página; 3) Teste com dados inválidos (ex.: campos vazios, formatos incorretos) e verifique mensagens de erro adequadas; 4) Verifique se alterações são refletidas em outras partes do sistema, se aplicável; 5) Teste em diferentes navegadores e dispositivos para compatibilidade. Documente quaisquer falhas encontradas.

## TESTES

Analise o arquivo: C:\xampp\htdocs\easy-budget-laravel\database\seeders\DatabaseSeeder.php

// 4. Criar provedores de teste (opcional - apenas em desenvolvimento)

Com base nisso, gere um script para criar:

-  5 provedores com CNPJ aleatórios, incluindo todos os dados completos.
-  Para esses provedores: 10 clientes com CPF e 10 clientes com CNPJ, todos com dados completos.
-  5 provedores com CPF aleatórios, incluindo todos os dados completos.
-  Para esses provedores: 10 clientes com CPF e 10 clientes com CNPJ, todos com dados completos.

Apenas isso por enquanto. Quando terminar os orçamentos, faremos simulações deles também.
