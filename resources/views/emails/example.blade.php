{{-- Exemplo de uso dos componentes de e-mail com internacionalização --}}

<x-mail::message>
    # {{ __( 'emails.budget.subject.created', [ 'budget' => $budgetCode ], $locale ) }}

    {{ __( 'emails.budget.greeting', [ 'name' => $customerName ], $locale ) }}

    {{ __( 'emails.budget.line1.created', [], $locale ) }}

    {{-- Usando componente de painel para detalhes --}}
    <x-mail::panel>
        <strong>{{ __( 'emails.budget.details', [], $locale ) }}</strong><br>
        • {{ __( 'emails.budget.code_label', [], $locale ) }} {{ $budgetCode }}<br>
        • {{ __( 'emails.budget.customer_label', [], $locale ) }} {{ $customerName }}<br>
        • {{ __( 'emails.budget.total_label', [], $locale ) }} {{ $totalAmount }}<br>
        • {{ __( 'emails.budget.due_date_label', [], $locale ) }} {{ $dueDate }}
    </x-mail::panel>

    {{-- Usando componente de tabela para itens --}}
    @if( $items )
        <x-emails.components.table :headers="[
            __( 'emails.budget.description_label', [], $locale ),
            __( 'emails.common.quantity', [], $locale ),
            __( 'emails.budget.total_label', [], $locale )
        ]" :rows="$items" />
    @endif

    {{-- Usando componente de botão --}}
    <x-emails.components.button :url="$budgetUrl" color="primary">
        {{ __( 'emails.budget.button', [], $locale ) }}
    </x-emails.components.button>

    {{-- Usando componente de alerta para informações importantes --}}
    <x-emails.components.alert type="warning" :message="__( 'emails.budget.expires_in', [ 'days' => 7 ], $locale )" />

    {{-- Usando componente de badge para status --}}
    <p>
        {{ __( 'emails.common.status', [], $locale ) }}:
        <x-emails.components.badge :text="__( 'emails.budget.status.' . $status, [], $locale )" type="info" />
    </p>

    {{ __( 'emails.budget.url_fallback', [], $locale ) }}

    [{{ $budgetUrl }}]({{ $budgetUrl }})

    ---

    <strong>{{ __( 'emails.budget.company_info', [], $locale ) }}</strong><br>
    • {{ __( 'emails.budget.company_label', [], $locale ) }} {{ $companyName }}<br>
    • {{ __( 'emails.common.email', [], $locale ) }} {{ $companyEmail }}<br>
    • {{ __( 'emails.common.phone', [], $locale ) }} {{ $companyPhone }}

    {{ __( 'emails.budget.footer', [ 'app_name' => config( 'app.name' ) ], $locale ) }}

    <x-mail::subcopy>
        {{ __( 'emails.budget.subcopy', [ 'budget' => $budgetCode ], $locale ) }}
    </x-mail::subcopy>
</x-mail::message>
