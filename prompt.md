
     * Exemplos de uso:
     *
     * ┌── Orçamento (por ID ou Código) - [!] Limpa histórico e sincroniza status dos serviços
     * │   php artisan dev:update-status budget 1 approved
     * │   php artisan dev:update-status budget ORC-2025-12-000001 draft
     * │
     * ├── Serviço
     * │   php artisan dev:update-status service SERV-2026-01-000001 on_hold
     * │
     * ├── Agendamento (código do serviço ou ID do agendamento)
     * │   php artisan dev:update-status schedule SERV-2026-01-000001 confirmed
     * │   php artisan dev:update-status schedule 1 finished
     * │
     * └── Serviço + Agendamento (simultâneo)
     *     php artisan dev:update-status service SERV-2026-01-000001 scheduling --sch=cancelled
     *
Essa é uma excelente estratégia. Liberar recursos gradualmente (estratégia de Feature Flags ou Feature Toggles) permite que você teste novas funcionalidades com um grupo pequeno de usuários, valide o feedback e evite que um erro derrube o sistema para todo mundo de uma vez.
No ecossistema Laravel, existem três caminhos principais para você seguir, dependendo da complexidade que você precisa:
1. O Jeito Oficial: Laravel Pennant
O Laravel Pennant é um pacote oficial criado especificamente para gerenciar "flags" de funcionalidades de forma leve e elegante.
 * Como funciona: Você define um recurso (ex: new-dashboard) e decide quem tem acesso a ele.
  * Vantagem: Integração nativa com o ecossistema Laravel e suporte para drivers como banco de dados ou cookies.
  Exemplo rápido:
  No seu AppServiceProvider, você define a funcionalidade:
  use Laravel\Pennant\Feature;
  use App\Models\User;

  Feature::define('modulo-financeiro', fn (User $user) => $user->plan === 'premium');

  Na sua Blade ou Controller, você verifica:
  @feature('modulo-financeiro')
      <a href="/financeiro">Acessar Painel</a>
      @endfeature

      2. Abordagem Manual (Gates e)
