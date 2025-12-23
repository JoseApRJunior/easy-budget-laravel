@php($paginator = $p ?? null)
@if ($paginator)
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            @if ($show_info ?? false)
                <small class="text-muted mb-2 mb-md-0">
                    Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }} ({{ $paginator->total() }}
                    registros)
                </small>
            @endif
            <nav aria-label="Navegação de páginas">
                @php($sizeClass = isset($size) && $size === 'sm' ? 'pagination-sm' : '')
                <ul class="pagination {{ $sizeClass }} mb-0">
                    <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $paginator->url(max(1, $paginator->currentPage() - 1)) }}"
                            aria-label="Anterior">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                            <span class="ms-1 d-none d-md-inline">Anterior</span>
                        </a>
                    </li>
                    @php($start = max(1, $paginator->currentPage() - 2))
                    @php($end = min($paginator->lastPage(), $paginator->currentPage() + 2))

                    @if ($start > 1)
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->url(1) }}">1</a>
                        </li>
                        @if ($start > 2)
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        @endif
                    @endif

                    @for ($page = $start; $page <= $end; $page++)
                        <li class="page-item {{ $page === $paginator->currentPage() ? 'active' : '' }}">
                            <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                        </li>
                    @endfor

                    @if ($end < $paginator->lastPage())
                        @if ($end < $paginator->lastPage() - 1)
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        @endif
                        <li class="page-item">
                            <a class="page-link"
                                href="{{ $paginator->url($paginator->lastPage()) }}">{{ $paginator->lastPage() }}</a>
                        </li>
                    @endif

                    <li class="page-item {{ $paginator->currentPage() === $paginator->lastPage() ? 'disabled' : '' }}">
                        <a class="page-link"
                            href="{{ $paginator->url(min($paginator->lastPage(), $paginator->currentPage() + 1)) }}"
                            aria-label="Próximo">
                            <span class="me-1 d-none d-md-inline">Próximo</span>
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
@endif
