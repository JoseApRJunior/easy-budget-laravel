@extends( 'emails.layouts.base' )

@section( 'title', 'Confirme sua conta - ' . config( 'app.name', 'Easy Budget' ) )

@section( 'content' )
    <p>Olá <strong>{{ $first_name ?? 'usuário' }}</strong>,</p>

    <p>Para ativar sua conta, clique no botão abaixo..</p>

    <p style="text-align:center; margin:20px 0;">
        @include( 'emails.components.button', [
            'url'  => $confirmationLink ?? '#',
            'text' => 'Confirmar minha conta'
        ] )
    </p>

    <p>Se o botão acima não funcionar, copie e cole o seguinte link no seu navegador:</p>
            <p class="subcopy">{{ $confirmationLink ?? config( 'app.url' ) . '/login' }}</p>
            <p style="margin-top:18px;">Este link expira em 30 minutos.</p>
           @include( 'emails.components.notice', [
            'content' => '<strong>Link expirado ou não recebido?</strong><br>' .
                'Caso o link tenha expirado ou você não tenha recebido o e-mail inicial, ' .
                '<a href="' . route( 'verification.notice' ) . '" ' .
                'style="color: #065f46; text-decoration: underline; font-weight: 600;">' .
                'clique aqui para solicitar um novo e-mail de verificação</a>.'
        ] )


            <p>Se você não se cadastrou no {{ config( 'app.name', 'Easy Budget' ) }}, por favor ignore este e-mail.</p>

            @include( 'emails.components.panel', [
                'content' => 'Este é um e-mail automático, por favor não responda.'
            ] )
@endsection

@section( 'footerExtra' )
    Todos os direitos reservados
@endsection
