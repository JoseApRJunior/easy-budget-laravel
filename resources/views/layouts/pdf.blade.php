@extends('layouts.pdf_base')

@section('styles')
<style>
    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        color: #333;
        line-height: 1.5;
    }
    .container-fluid {
        width: 100%;
    }
    .row {
        width: 100%;
        display: block;
        clear: both;
    }
    .col-8 { width: 66.66%; float: left; }
    .col-4 { width: 33.33%; float: left; }
    .col-12 { width: 100%; }
    .text-end { text-align: right; }
    .text-center { text-align: center; }
    .text-primary { color: #0d6efd; }
    .text-muted { color: #6c757d; }
    .text-dark { color: #212529; }
    .mb-0 { margin-bottom: 0; }
    .mb-1 { margin-bottom: 0.25rem; }
    .mb-4 { margin-bottom: 1.5rem; }
    .mt-4 { margin-top: 1.5rem; }
    .my-4 { margin-top: 1.5rem; margin-bottom: 1.5rem; }
    .p-0 { padding: 0; }
    .py-1 { padding-top: 0.25rem; padding-bottom: 0.25rem; }
    hr { border: 0; border-top: 1px solid #dee2e6; }
    .bg-light { background-color: #f8f9fa; }
    .bg-secondary { background-color: #6c757d; }
    .table {
        width: 100%;
        margin-bottom: 1rem;
        vertical-align: top;
        border-color: #dee2e6;
        border-collapse: collapse;
    }
    .table-sm th, .table-sm td { padding: 0.25rem; }
    .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
    .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(0, 0, 0, 0.05); }
    .card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-width: 0;
        word-wrap: break-word;
        background-color: #fff;
        background-clip: border-box;
        border: 1px solid rgba(0, 0, 0, 0.125);
        border-radius: 0.25rem;
    }
    .card-body { flex: 1 1 auto; padding: 1rem; }
    .border-0 { border: 0 !important; }
</style>
@endsection

@section('footer')
<div style="text-align: center; font-size: 8pt; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 10px;">
    Página {PAGENO} de {nbpg} | Gerado em {{ now()->format('d/m/Y H:i:s') }}
</div>
@endsection
