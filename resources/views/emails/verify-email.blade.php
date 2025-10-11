{{ $content ?? '' }}

{{-- Template personalizado para email de verificação em português --}}

@component( 'mail::message' )
# Confirme seu endereço de e-mail

Olá {{ $user->provider?->commonData?->first_name ?? 'usuário' }}!

Clique no botão abaixo para confirmar seu endereço de e-mail e ativar sua conta no Easy Budget.

@component( 'mail::button', [ 'url' => $verificationUrl, 'color' => 'primary' ] )
Confirmar E-mail
@endcomponent

Se você não criou uma conta no Easy Budget, ignore este e-mail.

Atenciosamente,
Equipe Easy Budget

@component( 'mail::subcopy' )
Se você estiver tendo problemas para clicar no botão "Confirmar E-mail", copie e cole a URL abaixo em seu navegador:
<br>
[{{ $verificationUrl }}]({{ $verificationUrl }})
@endcomponent
@endcomponent
