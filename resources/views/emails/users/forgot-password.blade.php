@extends( 'emails.layouts.base' )

@section( 'title', 'Redefinição de Senha - ' . config( 'app.name', 'Easy Budget' ) )

@section( 'content' )
    <p>Olá <strong>{{ $first_name ?? 'usuário' }}</strong>,</p>

    <p>Você solicitou uma redefinição de senha para sua conta no {{ config( 'app.name', 'Easy Budget' ) }}.</p>

    <p>Clique no botão abaixo para redefinir sua senha. Este link é válido por 1 hora:</p>

    <p style="text-align:center; margin:20px 0;">
        @include( 'emails.components.button', [
            'url'  => $reset_link ?? '#',
            'text' => 'Redefinir Senha'
        ] )
    </p>

    <p><strong>Link de redefinição:</strong></p>
        <p class="subcopy">{{ $reset_link ?? config( 'app.url' ) . '/login' }}</p>
        <p style="margin-top:18px;"><strong>Válido até:</strong> {{ $expires_at ?? '1 hora' }}</p>
        <p>Se você não solicitou esta redefinição, por favor, ignore este e-mail.</p>

        @include( 'emails.components.panel', [
            'content' => 'Este é um e-mail automático, por favor não responda.'
        ] )

@endsection

@section( 'footerExtra' )
    Todos os direitos reservados
@endsection
