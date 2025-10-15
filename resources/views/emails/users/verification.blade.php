<x-mail::message>
    {{-- Cabeçalho simples e compatível --}}
    <div style="text-align: center; padding: 20px; background-color: #f8f9fa; border-radius: 8px; margin-bottom: 20px;">
        <h1 style="color: #3B82F6; margin: 0; font-size: 24px; font-weight: bold;">
            {{ config( 'app.name', 'Easy Budget' ) }}
        </h1>
        <p style="color: #6c757d; margin: 5px 0 0 0; font-size: 14px;">
            Sistema de Gestão Empresarial
        </p>
    </div>

    {{-- Saudação personalizada --}}
    <p style="font-size: 16px; line-height: 1.5; margin: 0 0 20px 0;">
        Olá <strong>{{ $user_name ?? $first_name ?? 'Valioso Cliente' }}</strong>,
    </p>

    {{-- Mensagem principal --}}
    <div
        style="background-color: #e3f2fd; border: 2px solid #3B82F6; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center;">
        <h2 style="color: #1976d2; margin: 0 0 15px 0; font-size: 20px;">
            📧 Confirme seu E-mail
        </h2>
        <p style="color: #1565c0; margin: 0 0 20px 0; font-size: 14px; line-height: 1.4;">
            Para ativar sua conta e começar a usar todos os recursos do {{ config( 'app.name', 'Easy Budget' ) }},
            clique no botão abaixo e confirme seu endereço de e-mail.
        </p>

        {{-- Botão de ação destacado --}}
        <x-mail::button :url="$verificationUrl" color="primary"
            style="background: #3B82F6; color: white; padding: 12px 24px; border-radius: 6px; font-weight: bold; text-decoration: none;">
            ✅ Confirmar E-mail Agora
        </x-mail::button>
    </div>

    {{-- Informações importantes em formato simples --}}
    <div
        style="background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 15px; margin: 20px 0;">
        <h3 style="color: #856404; margin: 0 0 10px 0; font-size: 16px;">
            📋 Detalhes da Verificação
        </h3>
        <p style="margin: 5px 0; color: #856404; font-size: 14px;">
            <strong>E-mail:</strong> {{ $user_email ?? $user->email }}
        </p>
        <p style="margin: 5px 0; color: #856404; font-size: 14px;">
            <strong>Expira em:</strong> {{ $expiresAt ? $expiresAt->format( 'd/m/Y \à\s H:i' ) : '30 minutos' }}
        </p>
        <p style="margin: 5px 0; color: #856404; font-size: 14px;">
            <strong>Plataforma:</strong> {{ config( 'app.name', 'Easy Budget' ) }}
        </p>
        @if( $tenant )
            <p style="margin: 5px 0; color: #856404; font-size: 14px;">
                <strong>Empresa:</strong> {{ $tenant->name }}
            </p>
        @endif
    </div>

    {{-- Link alternativo em texto simples --}}
    <div style="text-align: center; margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-radius: 6px;">
        <p style="margin: 0 0 8px 0; color: #495057; font-size: 13px;">
            Caso o botão não funcione, copie e cole este link no seu navegador:
        </p>
        <p
            style="margin: 0; font-family: monospace; background: white; padding: 8px; border-radius: 4px; border: 1px solid #dee2e6; word-break: break-all; font-size: 12px;">
            {{ $verificationUrl }}
        </p>
    </div>

    {{-- Seção de segurança --}}
    <div
        style="background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 15px; margin: 20px 0;">
        <h4 style="color: #856404; margin: 0 0 8px 0; font-size: 14px;">
            🔒 Segurança
        </h4>
        <p style="margin: 0; color: #856404; font-size: 13px; line-height: 1.4;">
            Este link de verificação expira automaticamente em 30 minutos por motivos de segurança.
            Se você não solicitou esta verificação, pode ignorar este e-mail com segurança.
        </p>
    </div>

    {{-- Footer profissional --}}
    <div style="text-align: center; padding: 15px; background-color: #f8f9fa; border-radius: 6px; margin-top: 20px;">
        <p style="margin: 0 0 10px 0; color: #495057; font-size: 13px;">
            Obrigado por escolher o {{ config( 'app.name', 'Easy Budget' ) }}!
        </p>
        <p style="margin: 0 0 5px 0; color: #6c757d; font-size: 11px;">
            Este é um e-mail automático, por favor não responda.
        </p>
        @if( $supportEmail )
            <p style="margin: 0; color: #6c757d; font-size: 11px;">
                Precisa de ajuda? Entre em contato: <a href="mailto:{{ $supportEmail }}"
                    style="color: #3B82F6;">{{ $supportEmail }}</a>
            </p>
        @endif
    </div>

    {{-- Subcopy para clientes de e-mail que não suportam HTML --}}
    <x-mail::subcopy>
        Olá {{ $user_name ?? $first_name ?? 'usuário' }},

        Bem-vindo ao {{ config( 'app.name', 'Easy Budget' ) }}!

        Para confirmar seu e-mail e ativar sua conta, acesse:
        {{ $verificationUrl }}

        Este link expira em 30 minutos.

        Se você não solicitou esta verificação, ignore este e-mail.

        Atenciosamente,
        Equipe {{ config( 'app.name', 'Easy Budget' ) }}
    </x-mail::subcopy>
</x-mail::message>
