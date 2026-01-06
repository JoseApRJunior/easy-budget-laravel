@extends('layouts.admin')

@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            title="Logs do Sistema"
            icon="file-earmark-text"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Logs' => '#'
            ]">
        </x-page-header>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Visualizador de Logs</h5>
                <form id="log-date-filter" method="GET" action="{{ url('/admin/logs') }}"
                    class="d-flex align-items-center">
                    <label for="log-date" class="form-label me-2 mb-0">Selecionar Data:</label>
                    <select name="date" id="log-date" class="form-select form-select-sm" style="width: auto;"
                        onchange="this.form.submit()">
                        @foreach ($logDates as $date)
                            <option value="{{ $date }}" @if ($date == $selectedDate) selected @endif>
                                {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                            </option>
                        @endforeach
                        @if (!in_array($selectedDate, $logDates))
                            <option value="{{ $selectedDate }}" selected>
                                {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} (Vazio)
                            </option>
                        @endif
                    </select>
                    <noscript><button type="submit" class="btn btn-primary btn-sm ms-2">Ver</button></noscript>
                </form>
            </div>
            <div class="card-body">
                @if ($logs)
                    <div class="accordion" id="logAccordion">
                        @foreach ($logs as $log)
                            @php
                                $level_class =
                                    [
                                        'ERROR' => 'danger',
                                        'WARNING' => 'warning',
                                        'INFO' => 'info',
                                        'DEBUG' => 'secondary',
                                    ][$log['level']] ?? 'light';
                            @endphp
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-{{ $loop->index }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapse-{{ $loop->index }}" aria-expanded="false"
                                        aria-controls="collapse-{{ $loop->index }}">
                                        <span class="badge bg-{{ $level_class }} me-3">{{ $log['level'] }}</span>
                                        <span class="fw-bold me-3">{{ $log['datetime'] }}</span>
                                        <span class="text-truncate">{{ $log['message'] }}</span>
                                    </button>
                                </h2>
                                <div id="collapse-{{ $loop->index }}" class="accordion-collapse collapse"
                                    aria-labelledby="heading-{{ $loop->index }}" data-bs-parent="#logAccordion">
                                    <div class="accordion-body bg-light">
                                        @if ($log['is_html'])
                                            <iframe srcdoc="{{ $log['details'] }}"
                                                style="width: 100%; height: 500px; border: 1px solid #ccc; border-radius: 5px;"></iframe>
                                        @else
                                            <pre
                                                style="background-color: #2d2d2d; color: #f1f1f1; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word;">{{ $log['message'] }}<br>{{ trim($log['details']) ?: 'Nenhum detalhe adicional.' }}</pre>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        Nenhum log encontrado.
                    </div>
                @endif
            </div>
            <div class="card-footer bg-transparent border-0">
                <p class="text-muted small mb-0">Exibindo logs do arquivo:
                    <span class="text-code">storage/logs/app-{{ $selectedDate }}.log</span>
                </p>
            </div>
        </div>
    </div>
@endsection
