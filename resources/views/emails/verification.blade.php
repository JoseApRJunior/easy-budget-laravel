<x-mail::message>
    # {{ __( 'emails.verification.subject', [ 'app_name' => config( 'app.name', 'Easy Budget' ) ], $locale ) }}

    {{ __( 'emails.verification.greeting', [ 'name' => $user->name ?? $first_name ?? 'usuário' ], $locale ) }}

    {{ __( 'emails.verification.line1', [ 'app_name' => config( 'app.name', 'Easy Budget' ) ], $locale ) }}

    {{ __( 'emails.verification.line2', [], $locale ) }}

    <x-mail::button :url="$verificationUrl" color="primary">
        {{ __( 'emails.verification.button', [], $locale ) }}
    </x-mail::button>

    {{ __( 'emails.verification.line3', [], $locale ) }}

    <x-mail::panel>
        <strong>{{ __( 'emails.verification.details', [], $locale ) }}</strong><br>
        • {{ __( 'emails.verification.email_label', [], $locale ) }} {{ $user->email }}<br>
        • {{ __( 'emails.verification.expires_label', [], $locale ) }} {{ $expiresAt }}<br>
        • {{ __( 'emails.verification.platform_label', [], $locale ) }} {{ config( 'app.name', 'Easy Budget' ) }}
        @if( $tenant )
            <br>• {{ __( 'emails.verification.company_label', [], $locale ) }} {{ $tenant->name }}
        @endif
    </x-mail::panel>

    {{ __( 'emails.verification.line4', [], $locale ) }}

    [{{ $verificationUrl }}]({{ $verificationUrl }})

    ---

    <strong>{{ __( 'emails.verification.help', [], $locale ) }}</strong><br>
    @if( $supportEmail )
        {{ __( 'emails.verification.contact', [], $locale ) }} [{{ $supportEmail }}](mailto:{{ $supportEmail }})
    @endif

    {{ __( 'emails.verification.line5', [ 'app_name' => config( 'app.name', 'Easy Budget' ) ], $locale ) }}

    {{ __( 'emails.verification.footer', [ 'app_name' => config( 'app.name', 'Easy Budget' ) ], $locale ) }}

    <x-mail::subcopy>
        {{ __( 'emails.verification.subcopy', [], $locale ) }}
    </x-mail::subcopy>
</x-mail::message>
