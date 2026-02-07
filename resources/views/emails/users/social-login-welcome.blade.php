@extends( 'emails.layouts.base' )

@section( 'title', 'Bem-vindo via ' . ucfirst( $provider ) . ' - ' . config( 'app.name', 'Easy Budget' ) )

@section( 'content' )
  <p>Olá <strong>{{ $first_name }}</strong>,</p>

  <p>Bem-vindo ao {{ config( 'app.name', 'Easy Budget Laravel' ) }}! Sua conta foi criada e verificada automaticamente
    através do {{ ucfirst( $provider ) }}.</p>

  <p>Sua conta está ativa e pronta para uso. Você pode acessar o sistema a qualquer momento usando sua conta do
    {{ ucfirst( $provider ) }}.</p>

  <p><strong>Importante:</strong> Se precisar fazer login sem usar o {{ ucfirst( $provider ) }}, você pode alterar sua senha
    nas configurações da sua conta.</p>

  <p style="text-align:center; margin:20px 0;">
    @include( 'emails.components.button', [
      'url'  => route( 'settings.index' ),
      'text' => 'Acessar Configurações'
    ] )
  </p>

  <p>Se o botão não funcionar, copie e cole este link no seu navegador:</p>
      <p class="subcopy">{{ route( 'settings.index' ) }}</p>
      <p>Obrigado por escolher o {{ config( 'app.name', 'Easy Budget Laravel' ) }}!</p>

      @include( 'emails.components.panel', [
        'content' => 'Este é um e-mail automático, por favor não responda.'
      ] )

@endsection

@section( 'footerExtra' )
  Todos os direitos reservados
@endsection
