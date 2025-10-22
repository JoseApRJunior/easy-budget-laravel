# Guia Completo: Personalização de E-mails no Laravel

---

## Introdução

Personalizar e-mails em aplicações Laravel é uma prática essencial tanto para aprimorar a experiência do usuário quanto para garantir a consistência visual e o profissionalismo das comunicações do sistema. O framework oferece um conjunto robusto para criar, modelar, enviar e testar e-mails transacionais e notificações, seja com templates Blade, Markdown ou layouts customizados. Este guia técnico cobre detalhadamente desde a criação de templates e integração de variáveis dinâmicas até o uso de filas para envio assíncrono, internacionalização, testes avançados e boas práticas de segurança.

---

## 1. Criação e Configuração de Mailables

No Laravel, cada tipo de e-mail é representado por uma **classe Mailable**. Essas classes encapsulam lógica, estrutura e dados para cada mensagem, tornando as comunicações reutilizáveis e facilmente testáveis. O diretório padrão dessas classes é `app/Mail`.

### Gerando uma classe mailable com Artisan

```bash
php artisan make:mail NotificacaoUsuario
```

Ao executar este comando, uma nova classe é criada em `app/Mail/NotificacaoUsuario.php` e pode ser personalizada conforme a necessidade.

### Estrutura típica de uma classe Mailable

```php
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Address;

class NotificacaoUsuario extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $usuario) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('no-reply@meudominio.com', 'Equipe Meu Sistema'),
            subject: 'Bem-vindo ao Sistema'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bemvindo',
        );
    }
}
```

**Principais pontos**:

-  O método `envelope()` configura remetente, assunto, CC, BCC, Reply-to, tags e metadados.
-  O método `content()` indica o template Blade, HTML, texto simples ou Markdown a ser usado.
-  Dados podem ser passados por propriedades públicas, protegidas ou via o parâmetro `with` do método content.

---

## 2. Templates Blade Dinâmicos para E-mails

O Blade, engine de templates do Laravel, é a principal forma de modelar e-mails customizados. A integração de variáveis dinâmicas é feita de maneira intuitiva.

### Exemplo de template Blade

Arquivo: `resources/views/emails/bemvindo.blade.php`

```blade
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Bem-vindo</title>
</head>
<body>
    <h1>Olá, {{ $usuario->name }}!</h1>
    <p>Obrigado por se cadastrar em nosso site.</p>
</body>
</html>
```

### Passando dados dinâmicos

As propriedades públicas do Mailable são automaticamente acessíveis na view Blade. Alternativamente, utilize o parâmetro `with` no método `content`:

```php
public function content(): Content
{
    return new Content(
        view: 'emails.bemvindo',
        with: [
            'nome' => $this->usuario->name
        ]
    );
}
```

O Blade escapa as variáveis automaticamente (`{{ $variavel }}`), prevenindo ataques XSS.

---

## 3. Layouts de E-mail com Markdown e Temas Customizados

O Laravel permite criar templates de e-mail com **Markdown**, possibilitando layouts profissionais e consistentes com componentes para botões, tabelas e painéis.

### Gerando um Mailable com Markdown

```bash
php artisan make:mail NovaFatura --markdown=emails.novafatura
```

Isso cria:

-  `app/Mail/NovaFatura.php` (classe mailable)
-  `resources/views/emails/novafatura.blade.php` (template Markdown)

### Exemplo de template Markdown

```blade
<x-mail::message>
# Olá, {{ $nome }}

Segue sua fatura mensal.

<x-mail::button :url="$urlFatura">
Ver Fatura
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
```

### Componentes Markdown Disponíveis

-  **Botão**: `<x-mail::button :url="$url">Ver Fatura</x-mail::button>`
-  **Painel**: `<x-mail::panel>Este é um conteúdo em destaque.</x-mail::panel>`
-  **Tabela**: `<x-mail::table> ... </x-mail::table>`

### Personalizando componentes e temas

Publique os templates padrões para customização:

```bash
php artisan vendor:publish --tag=laravel-mail
```

Os arquivos ficam disponíveis em `resources/views/vendor/mail`. Altere `default.css` para customizar o tema global ou defina `$theme` no Mailable para temas por e-mail.

---

## 4. Configuração de Remetente e Envelope

O remetente pode ser definido globalmente no arquivo de configuração ou individualmente em cada mailable.

### Configuração global (`config/mail.php`):

```php
'from' => [
    'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
    'name' => env('MAIL_FROM_NAME', 'Meu Aplicativo'),
],
'reply_to' => [
    'address' => 'contato@meudominio.com',
    'name' => 'Suporte',
],
```

### Configuração individual via Envelope:

```php
public function envelope(): Envelope
{
    return new Envelope(
        from: new Address('no-reply@dominio.com', 'Equipe'),
        replyTo: [new Address('suporte@dominio.com', 'Suporte')],
        subject: 'Assunto Personalizado'
    );
}
```

Nesta estrutura, é possível ainda incluir CC, BCC, tags e metadados para rastreamento e organização dos e-mails.

---

## 5. Integração de Variáveis Dinâmicas

Riqueza e personalização dos e-mails dependem da integração de dados dinâmicos. Existem duas formas principais:

-  **Propriedades públicas**: disponíveis diretamente no Blade.
-  **Parâmetro `with` no método content**: ideal para evitar exposição desnecessária de objetos inteiros ou para renomear variáveis.

Exemplo via construtor:

```php
public function __construct(public $pedido) {}

// No Blade
Pedido nº: {{ $pedido->id }}

// Ou via with
public function content(): Content
{
    return new Content(
        view: 'emails.confirma-pedido',
        with: [
            'nomeCliente' => $this->pedido->cliente->nome,
            'total' => $this->pedido->total
        ]
    );
}
```

E no Blade:

```blade
<h1>Olá, {{ $nomeCliente }}</h1>
<p>Seu pedido foi realizado com sucesso. Total: R$ {{ $total }}</p>
```

---

## 6. Anexos e Recursos Inline em E-mails

### Enviando Anexos

No método `attachments()` da classe mailable, configure os arquivos a serem anexados:

```php
use Illuminate\Mail\Mailables\Attachment;

public function attachments(): array
{
    return [
        Attachment::fromPath('/caminho/relatorio.pdf')
            ->as('relatorio-mensal.pdf')
            ->withMime('application/pdf'),
    ];
}
```

Ou para anexos de storage cloud:

```php
public function attachments(): array
{
    return [
        Attachment::fromStorageDisk('s3', 'relatorio/2025.pdf')
            ->as('Relatorio___2025.pdf')
    ];
}
```

### Incorporando imagens inline

No Blade, utilize o método `embed`:

```blade
<img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="Logo">
```

Ou, para dados em memória:

```blade
<img src="{{ $message->embedData($imagem, 'logo.png') }}">
```

---

## 7. Templates Blade Customizados e Publicação de Templates do Laravel

O Laravel oferece templates padrão para notificações e e-mails no diretório `vendor`. Para customizar, publique-os:

```bash
php artisan vendor:publish --tag=laravel-mail
php artisan vendor:publish --tag=laravel-notifications
```

Esses comandos criam diretórios como `resources/views/vendor/mail` e `resources/views/vendor/notifications` com arquivos como:

-  `layout.blade.php`
-  `header.blade.php`
-  `footer.blade.php`
-  `button.blade.php`
-  `message.blade.php`
-  `email.blade.php` (notificações)

Aqui você ajusta o HTML conforme as necessidades do seu projeto, incluindo logo, CSS inline e outras customizações visuais.

---

## 8. Internacionalização de E-mails (i18n)

Laravel possui suporte robusto à internacionalização:

-  Defina o idioma padrão no `config/app.php` (`locale` e `fallback_locale`).
-  Armazene arquivos de tradução em `resources/lang/{idioma}/`.
-  Use funções como `__('chave')` no Blade ou `@lang()`.

### Exemplo de template multilíngue

Arquivo `resources/views/emails/boasvindas.blade.php`:

```blade
<h1>{{ __('emails.bemvindo.titulo', ['name' => $usuario->name]) }}</h1>
<p>{{ __('emails.bemvindo.mensagem') }}</p>
```

Arquivos de tradução:

-  `resources/lang/pt/emails.php`
-  `resources/lang/en/emails.php`

### Definindo idioma do e-mail

Ao enviar:

```php
Mail::to($usuario)->locale('es')->send(new NotificacaoUsuario($usuario));
```

Ou implementando a interface `HasLocalePreference` no modelo User:

```php
use Illuminate\Contracts\Translation\HasLocalePreference;
class User extends Model implements HasLocalePreference
{
    public function preferredLocale(): string
    {
        return $this->locale;
    }
}
```

---

## 9. Testes de E-mails em Ambiente de Desenvolvimento

### Visualização no Navegador

Adicione uma rota temporária para visualizar um mailable renderizado:

```php
Route::get('/mailable-preview', function () {
    $usuario = App\Models\User::first();
    return new App\Mail\NotificacaoUsuario($usuario);
});
```

Essa rota retorna a preview do email no browser, sem enviá-lo de fato.

### Ferramentas de Teste: Mailtrap e MailHog

**Mailtrap** e **MailHog** interceptam e exibem e-mails enviados em ambiente de desenvolvimento, sem entregá-los ao destinatário real.

#### Mailtrap – SMTP

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=seu_usuario
MAIL_PASSWORD=sua_senha
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=from@exemplo.com
MAIL_FROM_NAME=SeuApp
```

Acesse https://mailtrap.io para visualizar os e-mails enviados.

#### MailHog

Para instalar:

```bash
go install github.com/mailhog/MailHog@latest
MailHog
```

Configuração `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=from@exemplo.com
MAIL_FROM_NAME=SeuApp
```

Acesse http://localhost:8025 para visualizar os e-mails.

---

## 10. Testes Unitários e de Integração de E-mails

O Laravel facilita a automação dos testes relacionados a envio de e-mails com helpers e fakes.

### Usando Mail::fake()

```php
Mail::fake();

// Dispara ação que envia e-mail
$user->notify(new WelcomeNotification());

Mail::assertSent(WelcomeNotification::class);
Mail::assertSent(WelcomeNotification::class, function ($mail) use ($user) {
    return $mail->hasTo($user->email);
});
```

### Testando Mailables Manualmente

```php
public function testEmailEnviado()
{
    Mail::fake();
    $usuario = User::factory()->create();
    Mail::to($usuario->email)->send(new NotificacaoUsuario($usuario));
    Mail::assertSent(NotificacaoUsuario::class, function ($mail) use ($usuario) {
        return $mail->hasTo($usuario->email);
    });
}
```

Esses métodos permitem simular o envio e garantir que os e-mails corretos, nas condições apropriadas, sejam disparados.

---

## 11. Boas Práticas de Segurança e Escapamento

-  **Escapar por padrão**: O Blade escapa variáveis automaticamente usando `{{ }}`, protegendo contra XSS.
-  **Evite `{!! !!}`**: Só use para HTML confiável.
-  **Valide arquivos de anexo**: Implemente verificação de tipo e tamanho de arquivo para evitar vulnerabilidades.
-  **Evite links externos**: Principalmente em CSS, por motivos de segurança e compatibilidade.
-  **Utilize CSS inline**: Para maior compatibilidade entre clientes de e-mail.
-  **Teste em múltiplos clientes**: Ferramentas como Litmus ou Email on Acid garantem consistência na renderização.

---

## 12. Personalização e Edição de Templates de Notificação

Além de Mailables tradicionais, o Laravel suporta notificações que podem ser customizadas.

### Publicando os templates

```bash
php artisan vendor:publish --tag=laravel-notifications
```

Edite `resources/views/vendor/notifications/email.blade.php` ou crie novas classes de notificação customizadas para personalizar as notificações de verificação de e-mail, reset de senha, etc.

---

## 13. Fila de Envio e Performance

Enviar e-mails em fila aumenta a performance e evita atrasos na resposta de usuários.

### Usando queue na mailable

```php
Mail::to($usuario->email)->queue(new NotificacaoUsuario($usuario));
```

Ou para envio com atraso:

```php
Mail::to($usuario->email)->later(now()->addMinutes(10), new NotificacaoUsuario($usuario));
```

Implemente a interface `ShouldQueue` em seu Mailable para envio assíncrono automático:

```php
class NotificacaoUsuario extends Mailable implements ShouldQueue
```

Garanta que o worker esteja rodando:

```bash
php artisan queue:work
```

---

## 14. Recursos Avançados: Headers, Tags, Metadados

Acrescente tags e metadados no envelope para integração com sistemas de rastreamento:

```php
public function envelope(): Envelope
{
    return new Envelope(
        subject: 'Order Shipped',
        tags: ['envio'],
        metadata: ['pedido_id' => $this->pedido->id]
    );
}
```

Headers customizados também são suportados via método `headers()` na classe mailable:

```php
public function headers(): Headers
{
    return new Headers(
        messageId: 'custom-message@exemplo.com',
        references: ['anterior@exemplo.com'],
        text: ['X-Header-Personalizado' => 'Valor']
    );
}
```

---

## 15. Visualização de E-mails no Navegador

Além do uso do Mailtrap/MailHog, o Laravel permite visualizar o HTML renderizado de mailables diretamente em rotas:

```php
Route::get('/preview-email', function () {
    $pedido = App\Models\Order::first();
    return new App\Mail\OrderShipped($pedido);
});
```

Isto ajuda no desenvolvimento de layouts antes do disparo real.

---

## 16. Tabela: Principais Métodos e Classes para Personalização de E-mails

| Método/Classe                         | Descrição                                                             |
| ------------------------------------- | --------------------------------------------------------------------- |
| `php artisan make:mail`               | Cria uma nova classe mailable                                         |
| `Illuminate\Mail\Mailable`/`Mailable` | Classe base de e-mails personalizados                                 |
| `envelope()`/`Envelope`               | Remetente, assunto, CC, BCC, tags, metadados do e-mail                |
| `content()`/`Content`                 | Template Blade, Markdown, HTML, variáveis dinâmicas                   |
| `attachments()`/`Attachment`          | Lista de anexos, opções de nome/MIME, arquivos do disco/local/memória |
| `with()`                              | Variáveis adicionais para o template                                  |
| `Mail::to()->send()`                  | Envia o e-mail diretamente                                            |
| `Mail::to()->queue()`                 | Enfileira o envio do e-mail                                           |
| `ShouldQueue`                         | Interface para envio assíncrono                                       |
| `Mail::fake()`/`Mail::assertSent()`   | Utilitário para testes                                                |
| `Mail::alwaysTo()`                    | Redireciona envios em ambiente local                                  |
| `vendor:publish --tag=laravel-mail`   | Publica componentes Markdown para edição                              |
| `@component('mail::button', [...])`   | Botão em Markdown                                                     |
| `@component('mail::table')`           | Tabela em Markdown                                                    |
| `@component('mail::panel')`           | Painel em Markdown                                                    |
| `{{ }}`                               | Escape automático de variáveis no Blade                               |
| `queue:work`                          | Executor de tarefas em background (fila de emails)                    |

---

## 17. Exemplos Práticos de Código

### Mailable com Markdown, variáveis dinâmicas e anexo

```php
class NewOrderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $pedido) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('vendas@loja.com', 'Loja Online'),
            subject: 'Novo Pedido Recebido'
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pedido',
            with: [
                'cliente' => $this->pedido->cliente,
                'url' => route('pedido.show', $this->pedido->id)
            ]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath(storage_path("pedidos/{$this->pedido->id}.pdf"))->as("pedido-{$this->pedido->id}.pdf")
        ];
    }
}
```

No blade markdown:

```blade
<x-mail::message>
# Olá, {{ $cliente->nome }}

Seu pedido foi recebido. Para detalhes, clique abaixo.

<x-mail::button :url="$url">Ver Pedido</x-mail::button>

Confira o anexo para o resumo.
</x-mail::message>
```

---

## Conclusão

A personalização de e-mails no Laravel envolve uma combinação de recursos poderosos de templating, internacionalização, processamento assíncrono e segurança. Compreendendo e aplicando os métodos, classes e boas práticas listadas neste guia, é possível implementar comunicações transacionais robustas, bonitas, seguras e escaláveis em qualquer aplicação Laravel. Explorar ao máximo componentes Blade, layouts com Markdown, variáveis dinâmicas e ferramentas de teste como Mailtrap e MailHog transforma simples notificações em canais profissionais de relacionamento com o usuário, seja qual for o porte ou o segmento do projeto.

---

Ótimo! Vou preparar um guia técnico completo sobre como personalizar e-mails no Laravel, incluindo templates, layouts, variáveis dinâmicas, suporte a múltiplos idiomas e práticas de teste. Isso levará alguns minutos, então fique à vontade para fazer uma pausa — o conteúdo será salvo aqui na conversa para você acessar quando quiser. Até já!

**Para notificações de cadastro, reenvio e recuperação de senha, o ideal é usar _Mailables_ com templates Markdown e filas (queues). Já para mudanças de status em serviços, orçamentos e faturamento, recomendo usar _Notifications_ com canais múltiplos (e-mail, banco de dados, broadcast) e lógica condicional.**

Aqui vai uma análise detalhada para cada tipo de notificação:

---

### 🧾 1. Cadastro de usuário, reenvio e recuperação de senha

Essas notificações são **críticas e sensíveis**, então precisam ser:

-  **Seguras** (evitar exposição de dados)
-  **Claras e diretas**
-  **Personalizadas com nome e contexto**
-  **Rastreáveis** (logs ou banco de dados)

**Melhor abordagem:**

-  Use **Mailables** (`php artisan make:mail`) com **templates Markdown** (`resources/views/vendor/mail/html`).
-  Configure **filas** (`Queueable`) para não travar o processo de cadastro.
-  Use **assuntos personalizados** e **variáveis dinâmicas** (nome, link de ativação, IP, data).
-  Exemplo de uso:
   ```php
   Mail::to($user->email)->send(new WelcomeUserMail($user));
   ```

**Dica extra:** Use `Notification::route('mail', $user->email)->notify(new PasswordResetNotification($token));` para recuperação de senha com `Notifications`.

---

### 🛠️ 2. Mudança de status em serviços e orçamentos

Essas notificações são **transacionais e informativas**, então precisam ser:

-  **Automáticas e em tempo real**
-  **Contextuais** (mostrar status anterior e novo)
-  **Armazenadas para histórico**
-  **Possivelmente multicanal** (e-mail, painel, push)

**Melhor abordagem:**

-  Use **Notifications** (`php artisan make:notification`) com canal de **e-mail + database**.
-  Configure o `via()` para múltiplos canais:
   ```php
   public function via($notifiable)
   {
       return ['mail', 'database'];
   }
   ```
-  Use `Notification::send($users, new StatusChangedNotification($service))` para enviar em lote.

**Dica extra:** Use `broadcast` se quiser mostrar em tempo real no painel do usuário.

---

### 💰 3. Área de faturamento

Essas notificações são **financeiras e formais**, então precisam ser:

-  **Claras e detalhadas**
-  **Comprováveis** (PDF, links, valores)
-  **Armazenadas e auditáveis**
-  **Possivelmente com anexos**

**Melhor abordagem:**

-  Use **Mailables** com layout formal e possibilidade de **anexar PDFs** (boleto, nota fiscal).
-  Exemplo:
   ```php
   $email = new InvoiceMail($invoice);
   $email->attach(storage_path('invoices/'.$invoice->pdf));
   Mail::to($user->email)->send($email);
   ```

**Dica extra:** Use `tags` e `metadata` para rastrear entregas e aberturas.

---

### 🧠 Minha sugestão final

| Tipo de Notificação           | Melhor Abordagem     | Canal Ideal       | Extras Recomendados         |
| ----------------------------- | -------------------- | ----------------- | --------------------------- |
| Cadastro / Senha              | Mailables + Markdown | E-mail            | Fila, assunto personalizado |
| Status de Serviço / Orçamento | Notifications        | E-mail + Database | Broadcast, histórico        |
| Faturamento                   | Mailables + Anexo    | E-mail            | PDF, tags, logs             |

Se quiser, posso revisar seus templates atuais e sugerir melhorias visuais e de conteúdo. Quer que eu faça isso?
