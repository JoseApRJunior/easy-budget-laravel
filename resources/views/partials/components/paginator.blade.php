@php($paginator = $p ?? null)
@if($paginator)
<div class="card-footer">
    <div class="d-flex justify-content-center align-items-center gap-3">
        @if(($show_info ?? false))
            <small class="text-muted">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</small>
        @endif
        <nav aria-label="Navegação de páginas">
            @php($sizeClass = (isset($size) && $size === 'sm') ? 'pagination-sm' : '')
            <ul class="pagination {{ $sizeClass }} mb-0">
                <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $paginator->url(max(1, $paginator->currentPage()-1)) }}" aria-label="Anterior">Anterior</a>
                </li>
                @for($page = 1; $page <= $paginator->lastPage(); $page++)
                    <li class="page-item {{ $page === $paginator->currentPage() ? 'active' : '' }}">
                        <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                    </li>
                @endfor
                <li class="page-item {{ $paginator->currentPage() === $paginator->lastPage() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $paginator->url(min($paginator->lastPage(), $paginator->currentPage()+1)) }}" aria-label="Próximo">Próximo</a>
                </li>
            </ul>
        </nav>
    </div>
</div>
@endif
