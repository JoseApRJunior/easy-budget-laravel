@extends( 'emails.layouts.base' )

@section( 'title', 'Conta ' . ucfirst( $provider ) . ' vinculada - ' . config( 'app.name', 'Easy Budget' ) )

@section( 'content' )
  <p>Olá <strong>{{ $first_name }}</strong>,</p>

  <p>Sua conta do {{ ucfirst( $provider ) }} foi vinculada com sucesso à sua conta existente no
    {{ config( 'app.name', 'Easy Budget Laravel' ) }}!</p>

  <p>Agora você pode fazer login usando qualquer um dos métodos:</p>

  <ul style="margin: 20px 0; padding-left: 20px;">
    <li>Seu e-mail e senha cadastrados</li>
    <li>Sua conta do {{ ucfirst( $provider ) }}</li>
  </ul>

  <p>Esta vinculação permite maior flexibilidade no acesso à sua conta, mantendo todos os seus dados e configurações
    intactos.</p>

  <p style="text-align:center; margin:20px 0;">
    @include( 'emails.components.button', [
      'url'  => route( 'provider.dashboard' ),
      'text' => 'Acessar Dashboard'
    ] )
  </p>

  <p>Se o botão não funcionar, copie e cole este link no seu navegador:</p>
    <p class="subcopy">{{ route( 'provider.dashboard' ) }}</p>
    <p>Se você não solicitou esta vinculação, entre em contato conosco imediatamente.</p>

    @include( 'emails.components.panel', [
      'content' => 'Este é um e-mail automático, por favor não responda.'
    ] )


@endsection

@section( 'footerExtra' )
  Todos os direitos reservados
@endsection
