{{--
Componente de Paginação para Tabelas
Uso: @include('components.table-paginator')
--}}

{{-- Paginação --}}
<div class="card-footer bg-transparent border-0">
    <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small" id="pagination-info">
            {{-- Será preenchido via JavaScript --}}
        </div>
        <nav aria-label="Navegação de páginas" class="d-flex justify-content-center">
            <ul id="pagination" class="pagination pagination-sm mb-0">
                {{-- Será preenchido via JavaScript --}}
            </ul>
        </nav>
    </div>
</div>
