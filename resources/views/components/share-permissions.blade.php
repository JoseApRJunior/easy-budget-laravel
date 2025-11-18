@props(['permissions'])

<div class="d-flex flex-wrap gap-2">
    @foreach($permissions as $permission)
        @php
            $permissionConfig = [
                'view' => [
                    'icon' => 'bi-eye',
                    'label' => 'Visualizar',
                    'color' => 'primary'
                ],
                'approve' => [
                    'icon' => 'bi-check-circle',
                    'label' => 'Aprovar',
                    'color' => 'success'
                ],
                'comment' => [
                    'icon' => 'bi-chat',
                    'label' => 'Comentar',
                    'color' => 'info'
                ],
                'download' => [
                    'icon' => 'bi-download',
                    'label' => 'Download',
                    'color' => 'secondary'
                ]
            ];
            
            $config = $permissionConfig[$permission] ?? [
                'icon' => 'bi-question-circle',
                'label' => ucfirst($permission),
                'color' => 'dark'
            ];
        @endphp
        
        <span class="badge bg-{{ $config['color'] }}-subtle text-{{ $config['color'] }}">
            <i class="bi {{ $config['icon'] }} me-1"></i>
            {{ $config['label'] }}
        </span>
    @endforeach
</div>