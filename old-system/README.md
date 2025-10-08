# Easy-Budget

Easy-Budget é um sistema web para gerenciamento de orçamentos simples. Ele fornece uma interface intuitiva para criar, gerenciar e acompanhar orçamentos para diversos projetos ou serviços.

## Índice

-  [Funcionalidades](#funcionalidades)
-  [Requisitos](#requisitos)
-  [Instalação](#instalação)
-  [Uso](#uso)
-  [Documentação da API](#documentação-da-api)
-  [Estrutura do Projeto](#estrutura-do-projeto)
-  [Dependências](#dependências)
-  [Contribuindo](#contribuindo)
-  [Licença](#licença)

## Funcionalidades

-  Criar e gerenciar usuários ( prestadores de serviço ) e clientes
-  Autenticação e autorização de usuários e clientes
-  Criar e gerenciar orçamentos
-  Templating com Twig para views dinâmicas
-  Sistema de middleware personalizado para tratamento de requisições
-  Camada de abstração de banco de dados para operações simplificadas

## Requisitos

-  PHP 7.4 ou superior
-  Composer
-  Servidor web (ex: Apache, Nginx)
-  MySQL ou banco de dados compatível

## Instalação

1. Clone o repositório:
   bash
   git clone https://github.com/seuusuario/easy-budget.git
   cd easy-budget

2. Instale as dependências:

   bash
   composer install

3. Configure as variáveis de ambiente:

   1. Copie o arquivo de exemplo:

   bash
   cp .env.example .env

   2. Crie o arquivo de credenciais locais:
      cp .env.example .env.local

   3. Configure suas credenciais reais em .env.local:
      EMAIL_PASSWORD
      MERCADO_PAGO_ACCESS_TOKEN
      Outras credenciais sensíveis
      NUNCA commite o arquivo .env.local

   4. Para produçao
      .env.production (no servidor)
      APP_ENV=production
      APP_DEBUG=false
      EMAIL_PASSWORD=production_password
      MERCADO_PAGO_ACCESS_TOKEN=production_token

Edite o arquivo .env com os detalhes do seu banco de dados e outras configurações.

4. Configure o esquema do banco de dados (as instruções podem variar de acordo com sua configuração).

5. Configure seu servidor web para apontar para o diretório public como raiz do documento.

## Configuração Inicial do Banco de Dados

Após a instalação, você precisa configurar o banco de dados com os dados iniciais. Siga estas etapas:

1. Navegue até a pasta do projeto:

bash
cd easy-budget

2. Importe o arquivo SQL inicial para o seu banco de dados:
   mysql -u seu_usuario -p seu_banco_de_dados < doc/initial_data.sql
   Substitua seu_usuario e seu_banco_de_dados pelos valores apropriados que você configurou no arquivo .env.

3. Este script SQL criará as tabelas necessárias e inserirá dados iniciais, incluindo:
   Tabelas de usuários, roles e permissões
   Roles padrão (admin, manager, user)
   Permissões básicas
   Usuários de exemplo com senhas padrão (lembre-se de alterá-las em um ambiente de produção)

4. Após a importação, verifique se as tabelas foram criadas corretamente:
   mysql -u seu_usuario -p seu_banco_de_dados -e "SHOW TABLES;"

Agora seu banco de dados está configurado com os dados iniciais necessários para começar a usar o sistema.

Uso

1. Inicie seu servidor web.
2. Navegue até a URL do projeto em seu navegador.
3. Faça login com suas credenciais ou use uma das contas de exemplo criadas:
   Admin: admin@example.com
   Manager: manager@example.com
   User: user@example.com
   (A senha padrão para todas as contas é 'password123'. Lembre-se de alterá-las imediatamente em um ambiente de produção)
4. Comece a criar e gerenciar seus orçamentos!

## Documentação da API

A documentação da API pode ser gerada usando PHPDocumentor. Para gerar:

1. Execute o seguinte comando:

bash
composer generate-docs

2. Abra docs/api/index.html em seu navegador web para visualizar a documentação.

## Estrutura do Projeto

-  app/: Contém a lógica principal da aplicação (controllers, models, views, etc.)
-  core/: Classes e interfaces personalizadas do núcleo
-  http/: Classes relacionadas a HTTP (middleware, etc.)
-  public/: Arquivos acessíveis publicamente
-  tests/: Testes unitários e de integração

## Dependências

As principais dependências incluem:

-  vlucas/phpdotenv: Para gerenciamento de variáveis de ambiente
-  twig/twig: Para templating
-  respect/validation: Para validação de dados
-  phpmailer/phpmailer: Para envio de e-mails
-  nikic/fast-route: Para roteamento

Para uma lista completa de dependências, consulte o arquivo composer.json.

## Contribuindo

Contribuições são bem-vindas! Sinta-se à vontade para enviar um Pull Request.

1. Faça um fork do repositório
2. Crie sua branch de feature (git checkout -b feature/RecursoIncrivel)
3. Faça commit de suas mudanças (git commit -m 'Adiciona algum RecursoIncrivel')
4. Faça push para a branch (git push origin feature/RecursoIncrivel)
5. Abra um Pull Request

## Licença

Este projeto está licenciado sob a [Licença MIT](LICENSE).

Este README fornece uma visão geral abrangente do seu projeto Easy-Budget em português, incluindo suas funcionalidades, instruções de instalação, diretrizes de uso e estrutura do projeto. Também inclui seções sobre documentação da API, dependências e como contribuir para o projeto.

Você pode personalizar ainda mais este README adicionando detalhes mais específicos sobre a funcionalidade do seu projeto, opções de configuração ou qualquer outra informação relevante que seria útil para usuários e colaboradores.

```

```
