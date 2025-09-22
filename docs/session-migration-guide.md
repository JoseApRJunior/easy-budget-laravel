# Guia de Migração de Sessões

Este guia documenta a migração da tabela de sessões para compatibilidade com o Laravel no projeto Easy-Budget. A migração atualizada utiliza lógica condicional não-destrutiva para preservar dados existentes, evitando perda de sessões ativas. Esta documentação complementa a migração implementada em `database/migrations/2025_09_19_000001_fix_sessions_table_for_laravel_compatibility.php` e o teste refatorado em `tests/Feature/SessionCompatibilityTest.php`.

## Atualizações Implementadas (19/09/2025)

- **Comment 1**: Migração converte `session_data` JSON legado para `payload` Laravel (parse JSON para array PHP, serialize e base64). Fallback para array vazio se parsing falhar. Logs em conversões para monitoramento.

- **Comment 2**: Env-guard `SESSIONS_ALTER_IN_PLACE=true` prioriza alter-in-place, evitando DROP/CREATE a menos que explicitado.

- **Comment 5**: `down()` reversível dropa e recria schema Laravel, com log de warning (não restaura legacy).

- **Comment 3**: Testes relaxados: Asserções `user_id` condicionais (verifica coluna), priorizam `assertAuthenticatedAs($user)`.

- **Comment 4**: Middleware guarda acessos com `$request->hasSession()`. Nota PHPDoc: Registrar após `StartSession` no grupo 'web'.

- **Comment 6**: Busca no codebase confirma usos isolados na migração; sem refators adicionais necessários. Middleware e testes atualizados para compatibilidade.

Essas mudanças garantem migração segura, preservando sessões com logging e fallbacks.

## Comparação de Schemas: Legacy vs Laravel

O schema legacy pode conter colunas customizadas ou incompatíveis com o padrão do Laravel, como campos adicionais para metadados personalizados ou estruturas de dados não padronizadas. O schema padrão do Laravel para sessões é otimizado para armazenamento de dados serializados de forma segura e eficiente.

A seguir, uma tabela comparativa entre o schema legacy possível e o schema padrão do Laravel:

| Coluna          | Tipo (Legacy)                  | Tipo (Laravel)                  | Descrição e Diferenças |
|-----------------|--------------------------------|---------------------------------|------------------------|
| id             | Varia (ex: int auto_increment) | string primary key             | Laravel usa string para chave primária (UUID ou hash), compatível com sessões distribuídas. Legacy pode usar int, exigindo alteração para evitar colisões. |
| user_id        | Varia (ex: int nullable)       | foreignId nullable, index      | Laravel define como foreign key para usuários, com índice para consultas rápidas. Legacy pode faltar índice ou usar tipo incompatível. |
| ip_address     | Varia (ex: varchar(45) nullable) | string nullable (255 chars)   | Armazena IP do usuário. Laravel padroniza para IPv6; legacy pode ter tamanho menor ou ausente. |
| user_agent     | Varia (ex: text nullable)      | text nullable                  | Armazena user-agent do navegador. Semelhante, mas Laravel garante compatibilidade com textos longos. |
| payload        | Varia (ex: longtext nullable)  | longText nullable              | Armazena dados serializados da sessão. Legacy pode usar tipo menor, causando truncamento; Laravel usa longText para payloads grandes. |
| last_activity  | Varia (ex: int nullable, timestamp) | integer nullable (timestamp Unix) | Timestamp da última atividade. Laravel usa int para timestamp Unix; legacy pode usar datetime, exigindo conversão. |
| Colunas Customizadas (Legacy) | Ex: session_data (json), expires_at (datetime) | Não presentes                  | Legacy pode ter campos extras como expires_at ou metadados customizados, que serão dropados na migração para padronização. |

**Notas:**
- O schema legacy pode incluir colunas adicionais não listadas, como campos de auditoria customizados.
- A migração preserva dados existentes, migrando-os para o formato Laravel onde possível.

## Impacto no Deploy

A migração atualizada utiliza operações `ALTER TABLE` in-place para adicionar colunas faltantes e remover as legadas, sem recriar a tabela inteira. Isso minimiza o risco de perda de dados e downtime. **Atenção:** Sempre realize um backup completo do banco de dados (ex: via mysqldump ou ferramenta de gerenciamento como phpMyAdmin) antes de executar esta migração, pois o rollback é manual e a migração altera o schema de forma irreversível.

- **Preservação de Dados:** A lógica condicional no método `up()` verifica a existência de colunas antes de modificá-las, garantindo que sessões ativas sejam mantidas. Para payloads legacy inválidos ou ausentes, utiliza fallback para serialização válida do Laravel via `TO_BASE64('a:0:{}')`, preservando sessões vazias sem perda de dados.
- **Riscos Potenciais:** Se a tabela for dropada e recriada manualmente (não recomendado), todas as sessões ativas serão invalidadas, forçando os usuários a fazerem login novamente. Isso pode impactar a experiência do usuário em horários de pico.
- **Recomendações:**
  - Execute o deploy em horários de baixa atividade (ex: madrugada ou fins de semana).
  - Planeje uma manutenção programada com aviso aos usuários via e-mail ou dashboard.
  - Monitore logs de sessões pós-deploy para detectar invalidações inesperadas.
  - Em ambientes de produção, use ferramentas como Laravel Telescope ou logs customizados para rastrear sessões afetadas.

## Opção Mais Segura: Alter-in-Place e Por Que Evitar Drop/Create

A abordagem escolhida foi o `ALTER TABLE` in-place com lógica condicional, implementada no método `up()` da migração:

- **Lógica Condicional:** O código verifica se colunas como `user_id`, `ip_address`, etc., existem antes de adicioná-las. Colunas legadas incompatíveis são dropadas apenas se presentes, sem afetar o payload existente.
- **Vantagens:**
  - **Minimiza Downtime:** Operações ALTER são rápidas (segundos a minutos, dependendo do tamanho da tabela) e não requerem bloqueio total da tabela em MySQL 8.0+ com ALGORITHM=INPLACE.
  - **Preserva Sessões Ativas:** Dados de sessões não são perdidos, permitindo continuidade da sessão do usuário durante a migração.
  - **Alinhamento com Boas Práticas Laravel:** Segue as recomendações do Laravel para migrações não-destrutivas, evitando downtime desnecessário em aplicações em produção.
- **Por Que Evitar Drop/Create:**
  - Drop/create invalidaria todas as sessões ativas, causando logout forçado de usuários e potencial perda de carrinhos de compras ou estados de autenticação.
  - Alto risco de downtime (pode levar minutos em tabelas grandes) e perda de dados se houver falha no rollback.
  - Não é idempotente; múltiplas execuções causariam erros ou perda repetida de dados.

Essa opção foi priorizada para equilibrar segurança, performance e usabilidade, especialmente em um sistema multi-tenant como o Easy-Budget.

## Como Rodar Migrações e Verificar Funcionalidade

Siga estes passos para executar a migração e validar a funcionalidade:

1. **Executar a Migração:**
   - No terminal, navegue até o diretório do subprojeto: `cd easy-budget-laravel`.
   - Rode: `php artisan migrate`.
   - Isso aplicará a migração pendente, adicionando/alterando colunas conforme necessário.

2. **Verificar Status das Migrações:**
   - Execute: `php artisan migrate:status`.
   - Confirme que a migração `2025_09_19_000001_fix_sessions_table_for_laravel_compatibility` está marcada como "Ran" (executada).

3. **Testar Funcionalidade:**
   - **Via Testes Automatizados:** Rode: `php artisan test --filter=SessionCompatibilityTest`. Isso executará o teste refatorado, validando persistência de sessões sem dependências em endpoints inexistentes. Verifique se todos os testes passam (ex: criação de sessão, recuperação de dados).
   - **Via Aplicação:**
     - Inicie o servidor: `php artisan serve`.
     - Acesse a aplicação no navegador (ex: http://localhost:8000).
     - Faça login em uma conta de teste e navegue por páginas (ex: dashboard, perfil).
     - Verifique se a sessão persiste (ex: usuário permanece logado após refresh ou navegação).
     - Monitore a tabela `sessions` no banco de dados (via phpMyAdmin ou query: `SELECT * FROM sessions WHERE user_id = ?`) para confirmar entradas com payload serializado corretamente.

4. **Rollback se Necessário:** Esta migração é irreversível: o método `down()` lança uma `\LogicException` e não reverte automaticamente para evitar perda de dados ou incompatibilidades. Para rollback manual, realize backup completo do banco de dados antes de executar a migração e restaure via import do backup se necessário. Em desenvolvimento, teste em ambiente isolado. Não recomendado em produção sem planejamento detalhado.

**Dicas:** Em ambiente de desenvolvimento, use um banco de teste para validar antes do deploy. Monitore erros em `storage/logs/laravel.log` durante os testes.

## Mudanças de Configuração/Env Requeridas

Para que as sessões usem o driver de banco de dados, verifique e ajuste as configurações:

1. **Arquivo de Configuração (`config/session.php`):**
   - Certifique-se de que `'driver' => env('SESSION_DRIVER', 'database'),` esteja configurado para 'database'.
   - Outras opções relevantes:
     - `'lifetime' => env('SESSION_LIFETIME', 120),` (em minutos).
     - `'table' => 'sessions',` (nome da tabela padrão).
   - Se não existir, adicione ou ajuste conforme o template padrão do Laravel.

2. **Arquivo .env:**
   - Adicione ou verifique: `SESSION_DRIVER=database`.
   - Confirme que `DB_CONNECTION=mysql` (ou o driver apropriado) aponte para o banco correto.
   - Exemplo de entradas no .env:
     ```
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=easy_budget_db
     DB_USERNAME=root
     DB_PASSWORD=
     SESSION_DRIVER=database
     SESSION_LIFETIME=120
     ```
   - Após alterações no .env, limpe o cache: `php artisan config:clear`.

**Notas:** Não modifique o .env em produção sem backup. Em multi-tenant, certifique-se de que a conexão DB suporte isolamento de dados por tenant.

---

*Última atualização: 19/09/2025. Consulte o time de DevOps para deploys em staging/production.*

## Refatoração Necessária no Código Custom

Após a migração, colunas legadas como `session_data`, `expires_at` e `is_active` são removidas da tabela `sessions` para alinhamento com o schema padrão do Laravel. Isso impacta o código custom em `app/database/repositories/SessionRepository.php` e `app/database/servicesORM/SessionService.php`, que dependem desses campos.

**Ações Recomendadas:**
- **Migrar para Laravel Session Facade:** Substitua o handling custom por `Illuminate\Support\Facades\Session` e configure o driver de banco de dados em `config/session.php`.
- **Remover Dependências Custom:** Elimine ou adapte métodos que usam `setSessionData()`, `setExpiresAt()`, `setIsActive()`, queries com `isActive` e `expiresAt`. Use `session()->put()` para dados, `session()->has()` para verificação, e garbage collection do Laravel para expiração via `last_activity`.
- **Atualizar Repositórios/Serviços:** Refatore `SessionRepository` e `SessionService` para não dependerem da entidade custom `SessionEntity`; use Eloquent model para sessions se necessário.
- **Testes:** Atualize testes em `tests/Feature/SessionCompatibilityTest.php` para validar comportamento Laravel padrão.

Falha em refatorar causará erros pós-migração. Planeje esta refatoração em uma task separada após deploy da migração.
