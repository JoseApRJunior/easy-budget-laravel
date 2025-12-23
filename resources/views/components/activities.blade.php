@props(['activities' => [], 'translations' => [], 'total' => 0])

<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-activity me-2"></i>
            {{ $translations['activities_title'] ?? 'Atividades Recentes' }}
        </h5>
        <span class="badge bg-primary">{{ $total }}</span>
    </div>
    <div class="card-body">
        @if (empty($activities))
            <div class="text-center py-1">
                <i class="bi bi-activity text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">{{ $translations['no_activities'] ?? 'Nenhuma atividade recente' }}</p>
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach ($activities as $activity)
                    <div class="list-group-item px-0">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar avatar-sm">
                                    <div
                                        class="avatar-initial bg-{{ $activity['color'] ?? 'primary' }} rounded-circle">
                                        <i class="bi bi-{{ $activity['icon'] ?? 'activity' }}"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $activity['title'] ?? $activity['action'] }}</h6>
                                        <p class="text-muted small mb-0">{{ $activity['description'] ?? '' }}</p>
                                    </div>
                                    <small
                                        class="text-muted">{{ $activity['time'] ?? $activity['created_at']?->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @if ($total > 0)
        <div class="card-footer text-center">
            <a href="#" class="text-decoration-none">
                <small>{{ $translations['view_all_activities'] ?? 'Ver todas as atividades' }}</small>
                <i class="bi bi-chevron-right ms-1"></i>
            </a>
        </div>
    @endif
</div>
