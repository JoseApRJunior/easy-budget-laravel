@extends( 'emails.layouts.base' )

@section( 'title', 'Bem-vindo - ' . config( 'app.name', 'Easy Budget' ) )

@section( 'content' )
    <p>Olá <strong>{{ $first_name ?? 'usuário' }}</strong>,</p>

    <p>Obrigado por se cadastrar. Para ativar sua conta, clique no botão abaixo.</p>

    <p style="text-align:center; margin:20px 0;">
        @include( 'emails.components.button', [
            'url'  => $confirmationLink ?? '#',
            'text' => 'Confirmar minha conta'
        ] )
    </p>
    <p>Se o botão não funcionar, copie e cole este link no seu navegador:</p>
            <p class="subcopy">{{ $confirmationLink ?? config( 'app.url' ) . '/login' }}</p>
            <p style="margin-top:18px;">Este link expira em 30 minutos.</p>


    <p>Se você não se cadastrou no {{ config( 'app.name', 'Easy Budget' ) }}, ignore este e-mail.</p>


        @include( 'emails.components.panel', [
            'content' => 'Este é um e-mail automático, por favor não responda.'
        ] )
@endsection

@section( 'footerExtra' )
    Todos os direitos reservados
@endsection
