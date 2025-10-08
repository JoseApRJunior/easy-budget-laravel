**Resumo da página “Memory Bank” da Kilo Code**  
O recurso *Memory Bank* permite que o assistente Kilo Code mantenha o contexto de projetos entre sessões, evitando a perda de memória típica de AIs e tornando o trabalho mais eficiente e contínuo.

---

### 🧠 O que é o Memory Bank?
- É um sistema de documentação estruturada que armazena informações sobre seu projeto.
- Permite que o Kilo Code compreenda e lembre do seu projeto em sessões futuras.
- Os arquivos são armazenados em `.kilocode/rules/memory-bank/` no repositório do projeto.

### 🚧 Problema Resolvido
- AIs normalmente “esquecem” tudo entre sessões.
- Isso exige reexplicações constantes ou análises completas do código, o que é lento e caro.
- O Memory Bank resolve isso com persistência de contexto.

### ✅ Benefícios
- *Independente da linguagem*: funciona com qualquer stack.
- *Compreensão rápida*: evita escaneamento completo do projeto a cada sessão.
- *Documentação viva*: os arquivos ajudam a manter o projeto bem documentado.
- *Maior eficiência*: menos tempo explicando, mais tempo produzindo.

### 📁 Estrutura dos Arquivos
- **brief.md**: visão geral do projeto (mantido manualmente).
- **product.md**: propósito, problemas resolvidos, experiência do usuário.
- **context.md**: progresso atual, decisões recentes, próximos passos.
- **architecture.md**: arquitetura do sistema, decisões técnicas.
- **tech.md**: tecnologias usadas, dependências, configurações.
- **tasks.md** (opcional): tarefas repetitivas documentadas.

### 🚀 Como usar
1. Crie a pasta `.kilocode/rules/memory-bank/`.
2. Escreva o `brief.md`.
3. Adicione instruções no `memory-bank-instructions.md`.
4. Ative o modo *Architect* e use o comando `initialize memory bank`.
5. Revise os arquivos gerados e atualize conforme necessário.

### 🔄 Atualizações
- Use `update memory bank` após mudanças significativas.
- Pode focar em arquivos específicos com comandos como `update memory bank using information from @/Makefile`.

### 📌 Indicadores de Status
- `[Memory Bank: Active]`: arquivos lidos com sucesso.
- `[Memory Bank: Missing]`: arquivos ausentes ou vazios.

### 🧩 Boas Práticas
- Mantenha os arquivos concisos e atualizados.
- Use arquivos adicionais para documentações mais complexas.
- Atualize após marcos importantes ou mudanças de direção.

Para mais detalhes, você pode acessar a página completa [aqui](https://kilocode.ai/docs/advanced-usage/memory-bank). Se quiser ajuda para configurar seu próprio Memory Bank, posso te guiar passo a passo!