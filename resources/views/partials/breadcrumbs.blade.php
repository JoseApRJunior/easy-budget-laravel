@if(isset($breadcrumbs) && count($breadcrumbs) > 0)
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-gray-50 px-4 py-3 rounded-lg">
            <li class="breadcrumb-item">
                <a href="{{ route('home') }}" class="text-blue-600 hover:text-blue-800 transition-colors">
                    <i class="bi bi-house-door"></i>
                    <span class="sr-only">Home</span>
                </a>
            </li>
            
            @foreach($breadcrumbs as $breadcrumb)
                @if($loop->last)
                    <li class="breadcrumb-item active text-gray-700" aria-current="page">
                        @if(isset($breadcrumb['icon']))
                            <i class="bi {{ $breadcrumb['icon'] }} me-1"></i>
                        @endif
                        {{ $breadcrumb['title'] }}
                    </li>
                @else
                    <li class="breadcrumb-item">
                        <a href="{{ $breadcrumb['url'] }}" class="text-blue-600 hover:text-blue-800 transition-colors">
                            @if(isset($breadcrumb['icon']))
                                <i class="bi {{ $breadcrumb['icon'] }} me-1"></i>
                            @endif
                            {{ $breadcrumb['title'] }}
                        </a>
                    </li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif

{{-- Helper para definir breadcrumbs nas views --}}
@push('scripts')
<script>
// Função para adicionar breadcrumbs dinamicamente
function addBreadcrumb(title, url = null, icon = null) {
    // Esta função pode ser usada para adicionar breadcrumbs via JavaScript se necessário
    console.log('Breadcrumb adicionado:', { title, url, icon });
}
</script>
@endpush