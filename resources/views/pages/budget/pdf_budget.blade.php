@extends( 'layouts.pdf_base' )

@section( 'content' )
  @php
      $displayProvider = $provider ?? null;
      $displayTenant = $budget->tenant ?? null;
  @endphp
  <div class="container p-0 print-content" style="font-size: 11px;">
      <!-- Header Component -->
      <x-pdf.header
          :provider="$displayProvider"
          :tenant="$displayTenant"
        title="ORÇAMENTO"
        :code="$budget->code"
        :date="$budget->created_at"
        :dueDate="$budget->due_date"
        :status="$budget->status->getDescription()"
    />
    <x-pdf.separator />

    <!-- Customer Info Component -->
    <x-pdf.customer-info :customer="$budget->customer" />
    <x-pdf.separator />

    <!-- Budget Description -->
    @if( $budget->description )
    <x-pdf.section-block title="DESCRIÇÃO DO ORÇAMENTO">
        <p class="mb-0">{{ $budget->description }}</p>
    </x-pdf.section-block>
    <x-pdf.separator />
    @endif

    <!-- Linked Services -->
    @if( $budget->services->isNotEmpty() )
    <x-pdf.section-header title="SERVIÇOS E ITENS" />
    @foreach( $budget->services as $service )
        <x-pdf.service-item :service="$service" />
    @endforeach

    <!-- Totals Summary -->
    <x-pdf.totals
        :subtotal="$budget->services->sum('total')"
        :discount="$budget->discount ?? 0"
        :total="$budget->total"
    />
    <x-pdf.separator />
    @else
    <div class="alert alert-info py-2">Nenhum serviço vinculado a este orçamento.</div>
    @endif

    <!-- Conditions and Observations -->
    <x-pdf.info-grid
        :leftTitle="$budget->payment_terms ? 'CONDIÇÕES DE PAGAMENTO' : null"
        rightTitle="OBSERVAÇÕES"
    >
        @if($budget->payment_terms)
            <x-slot:left>
                <p class="mb-0 small">{{ $budget->payment_terms }}</p>
            </x-slot:left>
        @endif

        <x-slot:right>
            @if($budget->notes ?? null)
                <p class="mb-0 small">{{ $budget->notes }}</p>
            @endif
        </x-slot:right>
    </x-pdf.info-grid>
    <x-pdf.separator />

    <x-pdf.document-metadata
                :dueDate="$budget->due_date"
                :generatedAt="now()"
            />

    <!-- Signatures -->
    <x-pdf.signatures
        :providerName="$displayProvider && $displayProvider->commonData ? ($displayProvider->commonData->company_name ?: ($displayProvider->commonData->first_name . ' ' . $displayProvider->commonData->last_name)) : ($displayTenant->name ?? 'A EMPRESA')"
        :customerName="$budget->customer->first_name . ' ' . $budget->customer->last_name"
    />

    <!-- Footer Note -->
    <x-pdf.footer-note note="Este documento é um orçamento e não representa um compromisso de execução sem a devida aprovação." />


</div>
@endsection

@push( 'styles' )
<style>
    @page {
        margin: 5mm;
        size: A4 portrait;
    }

    /* Estilos específicos para este PDF que não estão no base */
    .print-content {
        width: 100%;
    }
</style>
@endpush
