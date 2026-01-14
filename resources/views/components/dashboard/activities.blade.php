<div class="card h-100 border-0 shadow-sm" @style([
    "--text-primary: " . config('theme.colors.text', '#1e293b') . ";",
    "--text-secondary: " . config('theme.colors.secondary', '#94a3b8') . ";",
])>
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold" style="color: var(--text-primary);">
            <i class="bi bi-activity me-2" style="color: {{ config('theme.colors.primary') }};"></i>
            {{ $translations['activities_title'] ?? 'Atividades Recentes' }}
        </h5>
        <span class="badge" style="background-color: {{ config('theme.colors.primary') }}; color: #fff;">{{ $total }}</span>
    </div>
    <div class="card-body">
        @if (empty($activities))
            <div class="text-center py-1">
                <i class="bi bi-activity" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.5;"></i>
                <p class="mt-2" style="color: var(--text-secondary);">{{ $translations['no_activities'] ?? 'Nenhuma atividade recente' }}</p>
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach ($activities as $activity)
                    @php
                        $activityColor = $activity['color'] ?? 'primary';
                        $themeColor = config("theme.colors.$activityColor", config('theme.colors.primary'));
                    @endphp
                    <div class="list-group-item px-0 border-light">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar avatar-sm">
                                    <div class="avatar-initial rounded-circle" style="background-color: {{ $themeColor }}1a; color: {{ $themeColor }};">
                                        <i class="bi bi-{{ $activity['icon'] ?? 'activity' }}"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1" style="color: var(--text-primary);">{{ $activity['title'] ?? $activity['action'] }}</h6>
                                        <p class="small mb-0" style="color: var(--text-secondary);">{{ $activity['description'] ?? '' }}</p>
                                    </div>
                                    <small style="color: var(--text-secondary); font-size: 0.75rem;">
                                        {{ $activity['time'] ?? $activity['created_at']?->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @if ($total > 0)
        <div class="card-footer bg-white border-top-0 pb-4 text-center">
            <a href="#" class="text-decoration-none" style="color: {{ config('theme.colors.primary') }};">
                <small>{{ $translations['view_all_activities'] ?? 'Ver todas as atividades' }}</small>
                <i class="bi bi-chevron-right ms-1"></i>
            </a>
        </div>
    @endif
</div>
