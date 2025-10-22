<x-mail::message>
    # Nova Fatura Disponível

    Olá {{ $invoiceData[ 'customer_name' ] }},

    @if( $customMessage )
        {{ $customMessage }}

    @else
        Você recebeu uma nova fatura do **{{ $appName }}**.
    @endif

    ---

    ## Detalhes da Fatura

    <x-mail::panel>
        **Número da Fatura:** {{ $invoiceData[ 'code' ] }}
        **Valor Total:** R$ {{ $invoiceData[ 'total' ] }}
        @if( $invoiceData[ 'subtotal' ] !== $invoiceData[ 'total' ] )
            **Subtotal:** R$ {{ $invoiceData[ 'subtotal' ] }}
        @endif
        @if( $invoiceData[ 'discount' ] !== '0,00' )
            **Desconto:** R$ {{ $invoiceData[ 'discount' ] }}
        @endif
        @if( $invoiceData[ 'due_date' ] )
            **Vencimento:** {{ $invoiceData[ 'due_date' ] }}
        @endif
        @if( $invoiceData[ 'payment_method' ] )
            **Forma de Pagamento:** {{ $invoiceData[ 'payment_method' ] }}
        @endif

        @if( $invoiceData[ 'notes' ] && $invoiceData[ 'notes' ] !== 'Fatura sem observações' )
            **Observações:**
            {{ $invoiceData[ 'notes' ] }}
        @endif
    </x-mail::panel>

    <x-mail::button :url="$publicLink" color="primary">
        Visualizar Fatura
    </x-mail::button>

    Se o botão acima não funcionar, copie e cole o seguinte URL em seu navegador:

    [{{ $publicLink }}]({{ $publicLink }})

    ---

    ## Informações da Empresa

    @if( $company[ 'company_name' ] )
        **Empresa:** {{ $company[ 'company_name' ] }}

        @if( $company[ 'email_business' ] || $company[ 'phone_business' ] )
            **Contato:**
        @endif
        @if( $company[ 'email_business' ] )
            - E-mail: [{{ $company[ 'email_business' ] }}](mailto:{{ $company[ 'email_business' ] }})
        @endif
        @if( $company[ 'phone_business' ] )
            - Telefone: {{ $company[ 'phone_business' ] }}
        @endif
    @endif

    ---

    **Precisa de ajuda com esta fatura?**
    @if( $supportEmail )
        Entre em contato conosco: [{{ $supportEmail }}](mailto:{{ $supportEmail }})
    @endif

    Atenciosamente,
    **Equipe {{ $appName }}**

    <x-mail::subcopy>
        Esta é uma notificação automática sobre sua fatura {{ $invoiceData[ 'code' ] }}.
    </x-mail::subcopy>
</x-mail::message>
