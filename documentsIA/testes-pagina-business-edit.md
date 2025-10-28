# Testes Abrangentes - Página Provider Business Edit

**URL da Página:** https://dev.easybudget.net.br/provider/business/edit

**Objetivo:** Verificar se os dados estão sendo salvos corretamente na página de edição de negócio do provider, incluindo validações, persistência e compatibilidade.

**Data de Criação:** 28/10/2025

**Responsável:** [Nome do Testador]

---

## 📋 Etapas de Teste

### Etapa 1: Inserção de Dados Válidos

**Objetivo:** Verificar se todos os campos obrigatórios e opcionais aceitam dados válidos e são salvos corretamente.

#### Campos a Testar:

-  **Nome da Empresa** (opcional): Texto válido (ex.: "Minha Empresa Ltda")
-  **CNPJ** (opcional): 14 dígitos sem formatação (ex.: "12345678000190")
-  **CPF** (opcional): 11 dígitos sem formatação (ex.: "12345678901")
-  **Área de Atuação** (obrigatório): Selecionar opção válida do dropdown
-  **Profissão** (obrigatório): Selecionar opção válida do dropdown
-  **Descrição Profissional** (opcional): Texto até 250 caracteres (ex.: "Descrição da empresa...")
-  **Email Empresarial** (opcional): Formato válido (ex.: "contato@empresa.com")
-  **Telefone Empresarial** (opcional): Texto até 20 caracteres (ex.: "(11) 99999-9999")
-  **Website** (opcional): URL válida (ex.: "https://www.empresa.com")
-  **CEP** (obrigatório): Formato 00000-000 ou 00000000 (ex.: "01234-567")
-  **Endereço** (obrigatório): Texto até 255 caracteres (ex.: "Rua das Flores")
-  **Número** (opcional): Texto até 20 caracteres (ex.: "123")
-  **Bairro** (obrigatório): Texto até 100 caracteres (ex.: "Centro")
-  **Cidade** (obrigatório): Texto até 100 caracteres (ex.: "São Paulo")
-  **Estado** (obrigatório): 2 caracteres (ex.: "SP")
-  **Logo da Empresa** (opcional): Imagem PNG/JPG/GIF/WebP até 2MB

#### Campos Pessoais (Nova Seção - Dados Pessoais):

-  **Primeiro Nome** (obrigatório): Texto até 100 caracteres (ex.: "João")
-  **Sobrenome** (obrigatório): Texto até 100 caracteres (ex.: "Silva Santos")
-  **Data de Nascimento** (opcional): Formato DD/MM/YYYY (ex.: "15/08/1985")
-  **Email Pessoal** (opcional): Formato válido (ex.: "joao.silva@email.com")
-  **Telefone Pessoal** (opcional): Formato (11) 99999-9999 (ex.: "(11) 98765-4321")

#### Passos:

1. Acesse a página como usuário provider logado.
2. Preencha todos os campos com dados válidos.
3. Clique em "Salvar" ou equivalente.
4. Verifique se a página redireciona para uma página de sucesso ou mostra mensagem positiva.
5. Recarregue a página e confirme se os dados persistem.

#### Resultado Esperado:

-  Dados salvos sem erros.
-  Mensagem de sucesso exibida.
-  Dados permanecem após recarregamento.

#### Falhas Encontradas:

-  [ ] Nenhum erro encontrado.
-  [ ] Descrever qualquer falha aqui.

---

### Etapa 2: Verificação de Persistência de Dados

**Objetivo:** Confirmar que os dados salvos persistem após recarregar a página e em sessões subsequentes.

#### Passos:

1. Após salvar dados válidos na Etapa 1, recarregue a página (F5).
2. Verifique se todos os campos mostram os dados salvos.
3. Feche o navegador, reabra e acesse novamente a página.
4. Confirme se os dados ainda estão presentes.
5. Teste em uma nova aba/janela do mesmo navegador.

#### Resultado Esperado:

-  Dados persistem em todas as situações.
-  Não há perda de dados entre sessões.

#### Falhas Encontradas:

-  [ ] Nenhum erro encontrado.
-  [ ] Descrever qualquer falha aqui.

---

### Etapa 3: Testes com Dados Inválidos

**Objetivo:** Verificar validações e mensagens de erro adequadas para dados incorretos.

#### Cenários a Testar:

-  **Campos obrigatórios vazios:** Deixe CEP, endereço, bairro, cidade, estado, área de atuação, profissão, primeiro nome e sobrenome vazios.
-  **CNPJ inválido:** Use menos de 14 dígitos (ex.: "1234567890123").
-  **CPF inválido:** Use menos de 11 dígitos (ex.: "1234567890").
-  **Email inválido:** Use formato incorreto (ex.: "email@invalido") para email empresarial e pessoal.
-  **CEP inválido:** Use formato incorreto (ex.: "12345678" sem hífen ou "12345-6789" com dígito extra).
-  **Estado inválido:** Use mais de 2 caracteres (ex.: "São Paulo").
-  **Website inválido:** Use URL incorreta (ex.: "www.empresa").
-  **Logo inválido:** Upload de arquivo não-imagem (ex.: .txt), imagem muito grande (>2MB), ou formato não suportado (ex.: .tiff).
-  **Texto muito longo:** Insira mais de 250 caracteres na descrição, mais de 255 no endereço, mais de 100 caracteres no primeiro nome/sobrenome, etc.
-  **Área/Profissão inválida:** Tente submeter com valores não existentes nos dropdowns.
-  **Data de nascimento inválida:** Use formato incorreto (ex.: "31/02/1990" ou "15-08-1985").
-  **Telefone pessoal inválido:** Use formato incorreto (ex.: "11987654321" sem máscara).

#### Passos:

1. Para cada cenário, preencha os campos com dados inválidos.
2. Clique em "Salvar".
3. Verifique se o formulário não é submetido e mostra mensagens de erro específicas.
4. Confirme se os erros são claros e em português.
5. Teste combinação de múltiplos erros.

#### Resultado Esperado:

-  Formulário não é submetido com dados inválidos.
-  Mensagens de erro específicas e claras para cada campo.
-  Campos inválidos destacados (ex.: borda vermelha).

#### Falhas Encontradas:

-  [ ] Nenhum erro encontrado.
-  [ ] Descrever qualquer falha aqui.

---

### Etapa 4: Verificação de Reflexo em Outras Partes do Sistema

**Objetivo:** Confirmar se alterações na página de negócio refletem em outras áreas do sistema.

#### Áreas a Verificar:

-  **Dashboard do Provider:** Verificar se nome da empresa aparece corretamente no cabeçalho.
-  **Cabeçalho/Sidebar:** Confirmar se informações de empresa são atualizadas (logo, nome).
-  **Relatórios:** Verificar se dados da empresa aparecem em relatórios financeiros.
-  **Perfil do Usuário:** Confirmar se dados de contato são refletidos na página de perfil.
-  **Orçamentos/Faturas:** Verificar se informações da empresa aparecem em PDFs gerados.
-  **Configurações:** Verificar se dados aparecem na página de configurações gerais.
-  **User Model getNameAttribute:** Verificar se o nome completo (first_name + last_name) é usado corretamente no sistema.
-  **E-mails de Notificação:** Confirmar se dados pessoais aparecem em e-mails enviados ao usuário.

#### Passos:

1. Salve alterações válidas na página de negócio.
2. Navegue para cada área listada acima.
3. Verifique se as informações atualizadas aparecem corretamente.
4. Teste geração de PDF de orçamento/fatura para confirmar dados.

#### Resultado Esperado:

-  Alterações refletem em todas as áreas relevantes.
-  Dados consistentes em todo o sistema.

#### Falhas Encontradas:

-  [ ] Nenhum erro encontrado.
-  [ ] Descrever qualquer falha aqui.

---

### Etapa 5: Testes de Compatibilidade

**Objetivo:** Verificar funcionamento em diferentes navegadores e dispositivos.

#### Navegadores a Testar:

-  Google Chrome (versão mais recente)
-  Mozilla Firefox (versão mais recente)
-  Microsoft Edge (versão mais recente)
-  Safari (se disponível no Windows)

#### Dispositivos a Testar:

-  Desktop (resolução 1920x1080)
-  Tablet (resolução 768x1024, orientação portrait/landscape)
-  Mobile (resolução 375x667, orientação portrait)
-  Mobile (resolução 414x896, orientação landscape)

#### Passos:

1. Execute as Etapas 1-4 em cada navegador/dispositivo.
2. Verifique layout responsivo (Bootstrap deve ajustar automaticamente).
3. Teste upload de logo em dispositivos móveis.
4. Confirme funcionamento de validações em todos os ambientes.

#### Resultado Esperado:

-  Funcionamento idêntico em todos os navegadores.
-  Layout responsivo adequado em todos os dispositivos.
-  Sem diferenças de comportamento.

#### Falhas Encontradas:

-  [ ] Nenhum erro encontrado.
-  [ ] Descrever qualquer falha aqui.

---

## 📊 Resumo dos Resultados

### Status Geral:

-  [ ] Todos os testes passaram.
-  [ ] Alguns testes falharam (detalhar abaixo).

### Responsável pelos Testes:

-  [Nome do Testador]

### Data de Execução:

-  [Data dos testes]

### Falhas Críticas:

-  Descrever qualquer falha que impeça o uso da funcionalidade.

### Falhas Menores:

-  Descrever problemas de UX ou melhorias sugeridas.

### Recomendações:

-  Sugestões para correções ou melhorias.

---

## 📝 Notas Adicionais

-  **Ambiente de Teste:** Usar conta de provider de teste com dados fictícios.
-  **Dados de Teste:** Criar empresa de teste específica para evitar interferência com dados reais.
-  **Backup:** Fazer backup de dados antes de testes extensivos.
-  **Documentação de Bugs:** Para cada falha, incluir screenshots e descrição detalhada.

**Última Atualização:** 28/10/2025 - Adicionados testes para campos pessoais (first_name, last_name, birth_date, email pessoal, telefone pessoal) e validações específicas.

---

## 📋 Checklist de Preparação para Testes

### Ambiente de Teste:

-  [ ] Conta de provider de teste criada
-  [ ] Dados fictícios preparados (CNPJ, CPF, endereços válidos)
-  [ ] Imagens de teste prontas (PNG, JPG, diferentes tamanhos)
-  [ ] Navegadores atualizados
-  [ ] Dispositivos móveis/tablet disponíveis
-  [ ] Conexão de internet estável
-  [ ] Backup de dados realizado (se necessário)

### Ferramentas de Teste:

-  [ ] Navegadores: Chrome, Firefox, Edge, Safari
-  [ ] Dispositivos: Desktop, tablet, mobile
-  [ ] Ferramentas de desenvolvimento abertas
-  [ ] Console do navegador monitorado
-  [ ] Network tab preparada para análise
-  [ ] Screenshots preparados para documentar falhas

### Cenários de Teste Preparados:

-  [ ] Dados válidos completos (incluindo campos pessoais)
-  [ ] Dados inválidos por campo (incluindo validações de campos pessoais)
-  [ ] Casos edge (limites de caracteres, formatos específicos para telefone e data)
-  [ ] Upload de arquivos (válidos e inválidos)
-  [ ] Cenários de erro (conexão, permissões)
-  [ ] Compatibilidade cross-browser
-  [ ] Responsividade mobile/tablet
-  [ ] Verificação de persistência de dados pessoais em CommonData e Contact
-  [ ] Teste de integração com User model (getNameAttribute)
