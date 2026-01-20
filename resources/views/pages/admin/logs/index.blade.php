<x-app-layout title="Logs do Sistema">
    <x-layout.page-container>
        <x-layout.page-header
            title="Logs do Sistema"
            icon="file-earmark-text"
            :breadcrumb-items="[
                'Admin' => route('admin.dashboard'),
                'Logs' => '#'
            ]">
            <x-slot:actions>
                <form id="log-date-filter" method="GET" action="{{ route('admin.logs.index') }}" class="d-flex align-items-center gap-2">
                    <label for="log-date" class="form-label mb-0 fw-bold text-muted small text-uppercase text-nowrap">Data:</label>
                    <select name="date" id="log-date" class="form-select form-select-sm" style="min-width: 150px;" onchange="this.form.submit()">
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
                </form>
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <h5 class="mb-0 text-primary fw-bold">
                                <i class="bi bi-terminal me-2"></i>Visualizador de Logs
                            </h5>
                            <span class="badge bg-light text-muted border">
                                Arquivo: storage/logs/laravel-{{ $selectedDate }}.log
                            </span>
                        </div>
                    </x-slot:header>

                    @if ($logs && count($logs) > 0)
                        <div class="accordion" id="logAccordion">
                            @foreach ($logs as $log)
                                @php
                                    $level_class = match($log['level']) {
                                        'ERROR' => 'danger',
                                        'WARNING' => 'warning',
                                        'INFO' => 'info',
                                        'DEBUG' => 'secondary',
                                        default => 'light'
                                    };
                                    $level_icon = match($log['level']) {
                                        'ERROR' => 'x-circle',
                                        'WARNING' => 'exclamation-triangle',
                                        'INFO' => 'info-circle',
                                        'DEBUG' => 'bug',
                                        default => 'circle'
                                    };
                                @endphp
                                <div class="accordion-item border mb-2 rounded overflow-hidden">
                                    <h2 class="accordion-header" id="heading-{{ $loop->index }}">
                                        <button class="accordion-button collapsed p-3 bg-white" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse-{{ $loop->index }}" aria-expanded="false"
                                            aria-controls="collapse-{{ $loop->index }}">
                                            <div class="d-flex align-items-center w-100">
                                                <span class="badge bg-{{ $level_class }} me-3" style="width: 80px;">{{ $log['level'] }}</span>
                                                <span class="text-muted small me-3 font-monospace">{{ $log['datetime'] }}</span>
                                                <span class="text-truncate flex-grow-1 fw-medium text-dark">{{ $log['message'] }}</span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse-{{ $loop->index }}" class="accordion-collapse collapse"
                                        aria-labelledby="heading-{{ $loop->index }}" data-bs-parent="#logAccordion">
                                        <div class="accordion-body bg-light p-0">
                                            @if ($log['is_html'])
                                                <iframe srcdoc="{{ $log['details'] }}"
                                                    style="width: 100%; height: 500px; border: none;"></iframe>
                                            @else
                                                <div class="p-3 bg-dark text-light font-monospace small" style="overflow-x: auto;">
                                                    <div class="mb-2 text-warning border-bottom border-secondary pb-2">
                                                        {{ $log['message'] }}
                                                    </div>
                                                    <pre class="m-0 text-light" style="white-space: pre-wrap;">{{ trim($log['details']) ?: 'Nenhum detalhe adicional.' }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-file-earmark-check display-1 text-muted opacity-25 mb-3 d-block"></i>
                            <h5 class="text-muted">Nenhum log encontrado para esta data.</h5>
                            <p class="text-muted small">O arquivo de log está vazio ou não existe.</p>
                        </div>
                    @endif
                </x-ui.card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>
</x-app-layout>
