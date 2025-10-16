<x-mail::message>
    # Bem-vindo ao Easy Budget, {{ $first_name }}!

    Obrigado por se cadastrar. Para ativar sua conta, clique no botão abaixo.<br>
    <br>

    <x-mail::button :url="$verificationUrl">
        Confirmar minha conta
    </x-mail::button>

    <br>
    Se o botão acima não funcionar, copie e cole o seguinte link no seu navegador:<br>
    <x-mail::subcopy>
        {{ $verificationUrl }}
    </x-mail::subcopy>

    <br>
    Este link expirará em 30 minutos.<br>
    <br>

    Se você não se cadastrou no Easy Budget, por favor ignore este e-mail.<br>
    <br>

    <x-mail::panel>
        Este é um e-mail automático, por favor não responda.
    </x-mail::panel>

    <x-slot name="footer">
        © {{ date( 'Y' ) }} Easy Budget. Todos os direitos reservados.
    </x-slot>
</x-mail::message>
