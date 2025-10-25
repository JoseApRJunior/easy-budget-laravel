{{--
Template de e-mail para notificação de vinculação de conta social.

Este template é usado quando uma conta social (Google, Facebook, etc.)
é vinculada a uma conta existente do usuário.
--}}

@extends( 'emails.layouts.base' )

@section( 'content' )
  <div style="text-align: center; margin-bottom: 30px;">
    <h1 style="color: #3B82F6; margin: 0; font-size: 28px; font-weight: bold;">
      Confirmação de Vinculação - {{ $provider ? ucfirst( $provider ) : 'Conta Social' }}
    </h1>
    <p style="color: #6B7280; margin: 10px 0 0 0; font-size: 16px;">
      Confirme a vinculação da sua conta para continuar
    </p>
  </div>

  <div style="background: #F9FAFB; padding: 30px; border-radius: 8px; margin: 20px 0;">
    <h2 style="color: #1F2937; margin: 0 0 20px 0; font-size: 20px;">
      🔗 Solicitação de Vinculação
    </h2>

    <p style="color: #4B5563; margin: 0 0 15px 0; line-height: 1.6;">
      Olá <strong>{{ $first_name ?? 'usuário' }}</strong>,
    </p>

    <p style="color: #4B5563; margin: 0 0 15px 0; line-height: 1.6;">
      Detectamos uma tentativa de login com sua conta <strong>{{ $provider ? ucfirst( $provider ) : 'social' }}</strong>.
      Para sua segurança, precisamos confirmar que você autoriza a vinculação desta conta à sua conta
      existente no <strong>{{ config( 'app.name', 'Easy Budget' ) }}</strong>.
    </p>

    <div style="background: #DBEAFE; border: 1px solid #BFDBFE; border-radius: 6px; padding: 15px; margin: 20px 0;">
      <p style="color: #1E40AF; margin: 0; font-size: 14px; line-height: 1.5;">
        <strong>Por que vincular sua conta?</strong>
      </p>
      <ul style="color: #1E40AF; margin: 10px 0 0 20px; padding: 0; font-size: 14px;">
        <li>Login mais rápido e conveniente</li>
        <li>Acesso unificado a todos os seus dados</li>
        <li>Segurança aprimorada da conta</li>
        <li>Recuperação de senha simplificada</li>
      </ul>
    </div>

    <p style="color: #4B5563; margin: 0 0 15px 0; line-height: 1.6;">
      Após a confirmação, você poderá fazer login usando tanto suas credenciais normais quanto sua conta
      <strong>{{ $provider ? ucfirst( $provider ) : 'social' }}</strong>.
    </p>

    @if( $provider === 'google' )
      <p style="color: #4B5563; margin: 0 0 15px 0; line-height: 1.6;">
        Para sua segurança, recomendamos que você mantenha uma senha forte em sua conta principal, mesmo com a vinculação do
        Google ativada.
      </p>
    @endif
  </div>

  <div style="text-align: center; margin: 30px 0;">
    @if( $token )
      <a href="{{ config( 'app.url' ) }}/auth/social/confirm-linking/{{ $token->token }}"
        style="background: #10B981; color: white; padding: 15px 35px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin-right: 10px; font-size: 16px;">
        ✅ Confirmar Vinculação
      </a>
    @endif
    <a href="{{ config( 'app.url' ) }}/login"
      style="background: #6B7280; color: white; padding: 15px 35px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; font-size: 16px;">
      Fazer Login Normal
    </a>
  </div>

  @if( $token )
    <div style="background: #FEF3C7; border: 1px solid #FCD34D; border-radius: 6px; padding: 15px; margin: 20px 0;">
      <p style="color: #92400E; margin: 0; font-size: 14px; line-height: 1.5;">
        <strong>⚠️ Importante:</strong> Para completar a vinculação da sua conta {{ ucfirst( $provider ) }},
        clique no botão "Confirmar Vinculação" acima. O link expira em 30 minutos por segurança.
        Se você não solicitou esta vinculação, pode ignorar este e-mail.
      </p>
    </div>
  @endif

  <div style="border-top: 1px solid #E5E7EB; padding-top: 20px; margin-top: 30px;">
    <p style="color: #6B7280; margin: 0 0 10px 0; font-size: 14px;">
      <strong>Detalhes da solicitação:</strong>
    </p>
    <p style="color: #9CA3AF; margin: 0; font-size: 12px;">
      Data: {{ now()->format( 'd/m/Y \à\s H:i' ) }}<br>
      Provedor: {{ $provider ? ucfirst( $provider ) : 'Social' }}<br>
      E-mail: {{ $email ?? 'Não informado' }}<br>
      Validade do link: 30 minutos
    </p>
  </div>

  <div style="text-align: center; margin-top: 30px;">
    <p style="color: #9CA3AF; margin: 0; font-size: 12px;">
      Se você não solicitou esta vinculação, pode ignorar este e-mail com segurança.
      Sua conta permanecerá protegida.
    </p>
  </div>
@endsection

@section( 'footer' )
  <p style="color: #9CA3AF; margin: 0; font-size: 12px; text-align: center;">
    Este é um e-mail automático do {{ config( 'app.name', 'Easy Budget' ) }}.
    Por favor, não responda diretamente a esta mensagem.
  </p>
@endsection
