<x-mail::message>
    # Bem-vindo ao {{ config( 'app.name', 'Easy Budget' ) }}, {{ $first_name ?? $user_name ?? 'usuário' }}!

    Obrigado por se cadastrar. Para ativar sua conta, clique no botão abaixo.

    <x-mail::button :url="$verificationUrl">
        Confirmar minha conta
    </x-mail::button>

    Se o botão não funcionar, copie e cole este link no seu navegador:
    <x-mail::subcopy>
        {{ $verificationUrl }}
    </x-mail::subcopy>

    Este link expira em 30 minutos.

    Se você não se cadastrou no {{ config( 'app.name', 'Easy Budget' ) }}, ignore este e‑mail.

    <x-mail::panel>
        Este é um e‑mail automático, por favor não responda.
    </x-mail::panel>

    <x-slot name="footer">
        © {{ date( 'Y' ) }} {{ config( 'app.name', 'Easy Budget' ) }}.
        @if( !empty( $supportEmail ) )
            • Suporte: {{ $supportEmail }}
        @endif
    </x-slot>
</x-mail::message>
