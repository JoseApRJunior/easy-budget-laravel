🧪 GUIA DE TESTE MANUAL - FLUXO COMPLETO DO USUÁRIO
=================================================

📋 RESUMO DOS TESTES AUTOMATIZADOS
==================================
✅ Testes de Sistema: 14/14 testes passaram (100% de sucesso)
✅ Testes de Rotas: 37/37 testes passaram (100% de sucesso)

🎯 AGORA VAMOS SIMULAR O FLUXO MANUAL COMPLETO
==============================================

PASSO 1: CADASTRO INICIAL DO USUÁRIO
------------------------------------
1. Acesse: https://dev.easybudget.net.br/register
2. Preencha os dados:
   - Nome: João Silva (Teste Completo)
   - Email: joao.silva.teste@email.com
   - Senha: 12345678
   - Confirme a senha: 12345678
3. Clique em "Registrar"
4. Verifique se foi redirecionado para página de verificação de email

PASSO 2: VERIFICAÇÃO DE EMAIL
----------------------------
1. Acesse o email cadastrado (use um email real ou verifique logs)
2. Procure o email de verificação do Easy Budget
3. Clique no link de verificação no email
4. Verifique se foi redirecionado para o dashboard

PASSO 3: ATUALIZAÇÃO DO PERFIL
------------------------------
1. Faça login com as credenciais criadas
2. Acesse: Menu → Perfil (/provider/profile)
3. Complete os dados:
   - Telefone: (11) 98765-4321
   - Endereço: Rua das Flores, 123
   - Cidade: São Paulo
   - Estado: SP
   - CEP: 01234-567
4. Clique em "Atualizar Perfil"
5. Verifique se os dados foram salvos

PASSO 4: CONFIGURAÇÃO INICIAL
------------------------------
1. Acesse: Menu → Configurações (/provider/settings)
2. Configure:
   - Nome da Empresa: Silva & Cia LTDA
   - CNPJ: 12.345.678/0001-90
   - Telefone Comercial: (11) 3456-7890
   - Endereço Comercial: Av. Paulista, 1000
3. Configure preferências de notificação
4. Salve as configurações

PASSO 5: CADASTRO DE CLIENTES
-----------------------------
1. Acesse: Menu → Clientes → Novo Cliente (/provider/customers/create)
2. Cadastre 3 clientes de teste:

CLIENTE 1:
- Nome: Maria Santos
- Email: maria.santos@empresa.com
- Telefone: (11) 99876-5432
- CPF: 123.456.789-09
- Endereço: Rua A, 100

CLIENTE 2:
- Nome: Pedro Oliveira
- Email: pedro.oliveira@negocios.com
- Telefone: (11) 98765-1234
- CPF: 987.654.321-00
- Endereço: Rua B, 200

CLIENTE 3:
- Nome: Ana Costa
- Email: ana.costa@servicos.com
- Telefone: (11) 91234-5678
- CPF: 456.789.123-01
- Endereço: Rua C, 300

PASSO 6: CADASTRO DE PRODUTOS
-----------------------------
1. Acesse: Menu → Produtos → Novo Produto (/provider/products/create)
2. Cadastre 3 produtos:

PRODUTO 1:
- Nome: Cadeira Executiva
- SKU: CAD-EXEC-001
- Preço: R$ 450,00
- Custo: R$ 250,00
- Estoque: 50 unidades
- Categoria: Móveis

PRODUTO 2:
- Nome: Notebook Dell i5
- SKU: NOT-DELL-001
- Preço: R$ 3.200,00
- Custo: R$ 2.400,00
- Estoque: 15 unidades
- Categoria: Eletrônicos

PRODUTO 3:
- Nome: Kit Escritório
- SKU: KIT-ESC-001
- Preço: R$ 150,00
- Custo: R$ 85,00
- Estoque: 100 unidades
- Categoria: Escritório

PASSO 7: CADASTRO DE SERVIÇOS
-----------------------------
1. Acesse: Menu → Serviços → Novo Serviço (/provider/services/create)
2. Cadastre 3 serviços:

SERVIÇO 1:
- Nome: Consultoria Empresarial
- Descrição: Análise e planejamento empresarial
- Preço: R$ 500,00/hora
- Duração: 120 minutos
- Categoria: Consultoria

SERVIÇO 2:
- Nome: Treinamento de Equipe
- Descrição: Capacitação profissional
- Preço: R$ 200,00/hora
- Duração: 480 minutos (8 horas)
- Categoria: Treinamento

SERVIÇO 3:
- Nome: Suporte Técnico
- Descrição: Manutenção e suporte de TI
- Preço: R$ 150,00/hora
- Duração: 60 minutos
- Categoria: Tecnologia

PASSO 8: CRIAÇÃO DE ORÇAMENTOS
-------------------------------
1. Acesse: Menu → Orçamentos → Novo Orçamento (/provider/budgets/create)
2. Crie orçamento para Maria Santos:

ORÇAMENTO 1:
- Cliente: Maria Santos
- Produtos: 2 Cadeiras Executivas (R$ 900,00)
- Serviços: 4h Consultoria (R$ 2.000,00)
- Subtotal: R$ 2.900,00
- Desconto: 10% (R$ 290,00)
- Total: R$ 2.610,00
- Validade: 30 dias

3. Crie orçamento para Pedro Oliveira:

ORÇAMENTO 2:
- Cliente: Pedro Oliveira
- Produtos: 1 Notebook Dell + 1 Kit Escritório (R$ 3.350,00)
- Serviços: 8h Treinamento (R$ 1.600,00)
- Subtotal: R$ 4.950,00
- Desconto: 5% (R$ 247,50)
- Total: R$ 4.702,50
- Validade: 15 dias

PASSO 9: GERAÇÃO DE FATURAS
----------------------------
1. Acesse: Menu → Orçamentos
2. Localize o orçamento da Maria Santos
3. Clique em "Converter em Fatura"
4. Configure:
   - Data de vencimento: 30 dias
   - Forma de pagamento: Boleto Bancário
   - Observações: Primeira fatura do contrato
5. Confirme a geração da fatura

6. Repita para o orçamento do Pedro Oliveira:
   - Data de vencimento: 15 dias
   - Forma de pagamento: Transferência Bancária
   - Observações: Pagamento à vista com desconto

PASSO 10: AGENDAMENTO DE SERVIÇOS
----------------------------------
1. Acesse: Menu → Agenda → Novo Agendamento (/provider/schedules/create)
2. Agende para Maria Santos:
   - Serviço: Consultoria Empresarial
   - Data/Hora: [Data atual + 2 dias] às 14:00
   - Duração: 2 horas
   - Observações: Reunião inicial de planejamento

3. Agende para Pedro Oliveira:
   - Serviço: Treinamento de Equipe
   - Data/Hora: [Data atual + 5 dias] às 09:00
   - Duração: 8 horas
   - Observações: Treinamento completo da equipe

PASSO 11: ENVIO DE EMAILS
---------------------------
1. Acesse: Menu → Configurações → Email
2. Teste o envio de email:
   - Destinatário: seu-email@teste.com
   - Assunto: Teste de Sistema Easy Budget
   - Mensagem: Este é um email de teste do sistema
3. Verifique se o email foi enviado com sucesso

PASSO 12: GERAÇÃO DE RELATÓRIOS
---------------------------------
1. Acesse: Menu → Relatórios (/provider/reports)
2. Gere os seguintes relatórios:

RELATÓRIO DE VENDAS:
- Período: Mês atual
- Tipo: Resumo de vendas
- Verifique valores totais de orçamentos e faturas

RELATÓRIO DE CLIENTES:
- Tipo: Análise de clientes
- Verifique novos clientes cadastrados

RELATÓRIO DE PRODUTOS:
- Tipo: Estoque e vendas
- Verifique produtos mais vendidos

RELATÓRIO FINANCEIRO:
- Período: Mês atual
- Tipo: Fluxo de caixa
- Verifique receitas e despesas

PASSO 13: TESTE DO SISTEMA DE QR CODE
----------------------------------------
1. Acesse: Menu → QR Code (/provider/qrcode)
2. Gere QR Code para:
   - Site da empresa
   - WhatsApp comercial
   - Link de agendamento
3. Teste a leitura dos QR codes gerados

PASSO 14: TESTE DO DASHBOARD
-----------------------------
1. Acesse: Dashboard Principal (/provider/dashboard)
2. Verifique:
   - Total de clientes (deve mostrar 3)
   - Total de orçamentos (deve mostrar 2)
   - Total de faturas (deve mostrar 2)
   - Próximos agendamentos
   - Gráficos de vendas
   - Aniversariantes do mês

PASSO 15: TESTE DE ASSINATURA DE PLANO
----------------------------------------
1. Acesse: Menu → Assinatura (/provider/subscription)
2. Verifique plano atual
3. Teste upgrade de plano (se disponível)
4. Verifique histórico de pagamentos
5. Teste cancelamento e reativação (em ambiente de teste)

PASSO 16: TESTE DE PERMISSÕES E SEGURANÇA
-------------------------------------------
1. Teste acesso com usuário não autenticado:
   - Tente acessar /provider/dashboard sem login
   - Deve redirecionar para página de login

2. Teste acesso a rotas protegidas:
   - Faça login e acesse áreas restritas
   - Verifique se o acesso é permitido corretamente

3. Teste logout:
   - Clique em logout
   - Verifique se foi redirecionado corretamente

PASSO 17: TESTE DE DESEMPENHO
------------------------------
1. Teste com múltiplos agendamentos:
   - Crie 20 agendamentos para o mês
   - Verifique performance do calendário

2. Teste com muitos produtos:
   - Cadastre 50 produtos diferentes
   - Teste busca e filtros

3. Teste geração de relatórios grandes:
   - Gere relatório com 100+ vendas
   - Verifique tempo de carregamento

PASSO 18: TESTE DE FUNCIONALIDADES AVANÇADAS
---------------------------------------------
1. Teste importação/exportação:
   - Exporte lista de clientes
   - Importe planilha de produtos

2. Teste notificações:
   - Configure lembretes de agendamento
   - Teste notificações por email

3. Teste integrações:
   - Teste integração com WhatsApp (se configurada)
   - Teste integração com gateways de pagamento

🎯 CHECKLIST FINAL DE VALIDAÇÃO
================================

✅ CADASTRO E AUTENTICAÇÃO
- [ ] Cadastro de novo usuário
- [ ] Verificação de email
- [ ] Login/logout
- [ ] Recuperação de senha

✅ GERENCIAMENTO DE DADOS
- [ ] Cadastro de clientes
- [ ] Cadastro de produtos
- [ ] Cadastro de serviços
- [ ] Atualização de perfil

✅ PROCESSOS COMERCIAIS
- [ ] Criação de orçamentos
- [ ] Geração de faturas
- [ ] Agendamento de serviços
- [ ] Envio de emails

✅ RELATÓRIOS E ANÁLISES
- [ ] Relatórios de vendas
- [ ] Relatórios financeiros
- [ ] Dashboard com métricas
- [ ] Exportação de dados

✅ FUNCIONALIDADES ESPECIAIS
- [ ] Sistema de QR Code
- [ ] Assinatura de planos
- [ ] Notificações
- [ ] Calendário de agendamentos

✅ SEGURANÇA E DESEMPENHO
- [ ] Controle de acesso
- [ ] Performance do sistema
- [ ] Validação de dados
- [ ] Logs de auditoria

📊 RESULTADO ESPERADO
=====================
Após executar todos os passos acima, o sistema deve:

1. Funcionar sem erros críticos
2. Processar todos os dados corretamente
3. Gerar relatórios consistentes
4. Manter integridade entre módulos
5. Proteger dados sensíveis
6. Performance aceitável (< 3s por página)

🚀 CONCLUSÃO
============
Este guia cobre todo o fluxo do usuário no sistema Easy Budget. 
Execute cada passo cuidadosamente e documente qualquer problema encontrado.

Status do Teste: ⏳ EM ANDAMENTO
Data do Teste: 15/11/2025
Responsável: Equipe de Testes
Ambiente: Desenvolvimento (dev.easybudget.net.br)