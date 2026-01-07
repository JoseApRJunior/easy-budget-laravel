@extends('layouts.app')

@section('title', 'Produtos')

@push('styles')
<style>
    /* Ocultar placeholder nativo do Chrome para inputs de data vazios */
    input[type="date"]::-webkit-datetime-edit-fields-wrapper {
        color: transparent;
    }
    input[type="date"]:focus::-webkit-datetime-edit-fields-wrapper,
    input[type="date"]:not(:placeholder-shown)::-webkit-datetime-edit-fields-wrapper,
    input[type="date"]:valid::-webkit-datetime-edit-fields-wrapper {
        color: inherit;
    }
</style>
@endpush

@section('content')
<x-page-container>
    <x-page-header
        title="Produtos"
        icon="box-seam"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Produtos' => route('provider.products.dashboard'),
            'Lista' => '#'
        ]">
        <p class="text-muted mb-0">Lista de todos os produtos registrados no sistema</p>
    </x-page-header>

    <x-filter-form :route="route('provider.products.index')" id="filtersFormProducts" :filters="$filters">
                <x-filter-field
                            col="col-md-4"
                            name="search"
                            label="Buscar"
                            placeholder="Nome, SKU ou Descrição"
                            :filters="$filters"
                        />

                        <x-filter-field
                            type="select"
                            col="col-md-2"
                            name="category"
                            label="Categoria"
                            :filters="$filters"
                        >
                            <option value="">Todas as categorias</option>
                            @foreach ($categories as $category)
                                @if ($category->parent_id === null)
                                    @if ($category->children->isEmpty())
                                        <option value="{{ $category->slug }}"
                                            {{ ($filters['category'] ?? '') == $category->slug ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @else
                                        <optgroup label="{{ $category->name }}">
                                            <option value="{{ $category->slug }}"
                                                {{ ($filters['category'] ?? '') == $category->slug ? 'selected' : '' }}>
                                                {{ $category->name }} (Geral)
                                            </option>
                                            @foreach ($category->children as $subcategory)
                                                <option value="{{ $subcategory->slug }}"
                                                    {{ ($filters['category'] ?? '') == $subcategory->slug ? 'selected' : '' }}>
                                                    {{ $subcategory->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endif
                            @endforeach
                        </x-filter-field>

                        <x-filter-field
                            type="select"
                            col="col-md-2"
                            name="active"
                            label="Status"
                            :filters="$filters"
                        >
                            @php($selectedActive = $filters['active'] ?? '1')
                            <option value="1" {{ $selectedActive === '1' ? 'selected' : '' }}>Ativo</option>
                            <option value="0" {{ $selectedActive === '0' ? 'selected' : '' }}>Inativo</option>
                            <option value="all" {{ $selectedActive === 'all' ? 'selected' : '' }}>Todos</option>
                        </x-filter-field>

                        <x-filter-field
                            type="select"
                            col="col-md-2"
                            name="per_page"
                            label="Por página"
                            :filters="$filters"
                        >
                            @php($pp = (int) ($filters['per_page'] ?? 10))
                            <option value="10" {{ $pp === 10 ? 'selected' : '' }}>10</option>
                            <option value="20" {{ $pp === 20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ $pp === 50 ? 'selected' : '' }}>50</option>
                        </x-filter-field>

                        <x-filter-field
                            type="select"
                            col="col-md-2"
                            name="deleted"
                            label="Registros"
                            :filters="$filters"
                        >
                            @php($selectedDeleted = $filters['deleted'] ?? 'current')
                            <option value="current" {{ $selectedDeleted === 'current' ? 'selected' : '' }}>Atuais</option>
                            <option value="only" {{ $selectedDeleted === 'only' ? 'selected' : '' }}>Deletados</option>
                            <option value="all" {{ $selectedDeleted === 'all' ? 'selected' : '' }}>Todos</option>
                        </x-filter-field>

                        <x-filter-field
                            col="col-md-2"
                            name="min_price"
                            label="Preço Mínimo"
                            placeholder="0,00"
                            class="currency-brl"
                            inputmode="decimal"
                            prefix="R$"
                            :filters="$filters"
                        />

                        <x-filter-field
                            col="col-md-2"
                            name="max_price"
                            label="Preço Máximo"
                            placeholder="0,00"
                            class="currency-brl"
                            inputmode="decimal"
                            prefix="R$"
                            :filters="$filters"
                        />

                        <x-filter-field
                            type="date"
                            col="col-md-2"
                            name="start_date"
                            label="Cadastro Inicial"
                            :filters="$filters"
                        />

                        <x-filter-field
                            type="date"
                            col="col-md-2"
                            name="end_date"
                            label="Cadastro Final"
                            :filters="$filters"
                        />
                    </x-filter-form>

            <x-resource-list-card
                title="Lista de Produtos"
                mobileTitle="Produtos"
                icon="list-ul"
                :total="$products instanceof \Illuminate\Pagination\LengthAwarePaginator ? $products->total() : count($products)"
            >
                <x-slot:headerActions>
                    <x-table-header-actions
                        resource="products"
                        :filters="$filters"
                        createLabel="Nova"
                    />
                </x-slot:headerActions>

                <x-slot:desktop>
                    <x-resource-table>
                        <x-slot:thead>
                            <tr>
                                <th>Imagem</th>
                                <th>Nome</th>
                                <th>SKU</th>
                                <th class="text-nowrap">Categoria</th>
                                <th class="text-nowrap">Preço de Venda</th>
                                <th class="text-nowrap">Margem</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </x-slot:thead>

                        <x-slot:tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td class="text-center">
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                            class="img-thumbnail"
                                            style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td>{{ $product->name }}</td>
                                    <td><span class="text-code">{{ $product->sku }}</span></td>
                                    <td>{{ $product->category->name ?? 'N/A' }}</td>
                                    <td class="text-nowrap">{{ $product->formatted_price }}</td>
                                    <td>
                                        @if($product->cost_price > 0)
                                            <span class="badge bg-{{ $product->profit_margin_percentage >= 30 ? 'success' : ($product->profit_margin_percentage >= 15 ? 'warning' : 'danger') }}">
                                                {{ number_format($product->profit_margin_percentage, 1, ',', '.') }}%
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <x-status-badge :item="$product" statusField="active" activeLabel="Ativo" inactiveLabel="Inativo" />
                                    </td>
                                    <x-table-actions>
                                        @if ($product->deleted_at)
                                        {{-- Produto deletado: visualizar e restaurar --}}
                                        <x-button type="link" :href="route('provider.products.show', $product->sku)" variant="info" icon="eye" title="Visualizar" />
                                        <x-button variant="success" icon="arrow-counterclockwise"
                                            data-bs-toggle="modal" data-bs-target="#restoreModal"
                                            data-restore-url="{{ route('provider.products.restore', $product->sku) }}"
                                            data-product-name="{{ $product->name }}" title="Restaurar" />
                                        @else
                                        {{-- Produto ativo: show, edit, delete --}}
                                        <x-button type="link" :href="route('provider.products.show', $product->sku)" variant="info" icon="eye" title="Visualizar" />
                                        <x-button type="link" :href="route('provider.products.edit', $product->sku)" icon="pencil-square" title="Editar" />
                                        <x-button variant="danger" icon="trash"
                                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                                            data-delete-url="{{ route('provider.products.destroy', $product->sku) }}"
                                            data-product-name="{{ $product->name }}" title="Excluir" />
                                        @endif
                                    </x-table-actions>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox mb-2" aria-hidden="true" style="font-size: 2rem;"></i>
                                        <br>
                                        @if (($filters['deleted'] ?? '') === 'only')
                                            Nenhum produto deletado encontrado.
                                            <br>
                                            <small>Você ainda não deletou nenhum produto.</small>
                                        @else
                                            Nenhum produto encontrado.
                                            <br>
                                            <small>Tente ajustar seus filtros ou cadastrar um novo produto.</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </x-slot:tbody>
                    </x-resource-table>
                </x-slot:desktop>

                <x-slot:mobile>
                    @forelse($products as $product)
                            <x-resource-mobile-item>
                                <x-slot:avatar>
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                        class="rounded"
                                        style="width: 40px; height: 40px; object-fit: cover;">
                                </x-slot:avatar>

                                <div class="fw-semibold">{{ $product->name }}</div>

                                <x-slot:description>
                                    <div class="d-flex gap-2 flex-wrap mb-1">
                                        <span class="badge bg-secondary">{{ $product->sku }}</span>
                                        <x-status-badge :item="$product" statusField="active" activeLabel="Ativo" inactiveLabel="Inativo" />
                                    </div>
                                    <small class="text-muted d-block">Venda: {{ $product->formatted_price }}</small>
                                    @if($product->cost_price > 0)
                                        <small class="text-{{ $product->profit_margin_percentage >= 30 ? 'success' : ($product->profit_margin_percentage >= 15 ? 'warning' : 'danger') }}">
                                            Margem: {{ number_format($product->profit_margin_percentage, 1, ',', '.') }}%
                                        </small>
                                    @endif
                                </x-slot:description>

                                <x-slot:actions>
                                    <x-table-actions mobile>
                                        <x-button type="link" :href="route('provider.products.show', $product->sku)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                        @if ($product->deleted_at)
                                            <x-button variant="success" size="sm" icon="arrow-counterclockwise"
                                                data-bs-toggle="modal" data-bs-target="#restoreModal"
                                                data-restore-url="{{ route('provider.products.restore', $product->sku) }}"
                                                data-product-name="{{ $product->name }}" title="Restaurar" />
                                        @else
                                            <x-button type="link" :href="route('provider.products.edit', $product->sku)" size="sm" icon="pencil-square" title="Editar" />
                                            <x-button variant="danger" size="sm" icon="trash"
                                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                data-delete-url="{{ route('provider.products.destroy', $product->sku) }}"
                                                data-product-name="{{ $product->name }}" title="Excluir" />
                                        @endif
                                    </x-table-actions>
                                </x-slot:actions>
                            </x-resource-mobile-item>
                        @empty
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                <br>
                                @if (($filters['deleted'] ?? '') === 'only')
                                    Nenhum produto deletado encontrado.
                                @else
                                    Nenhum produto encontrado.
                                @endif
                            </div>
                        @endforelse
                    </x-slot:mobile>
                <x-slot:footer>
                    @if ($products instanceof \Illuminate\Pagination\LengthAwarePaginator && $products->hasPages())
                        @include('partials.components.paginator', [
                            'p' => $products->appends(collect(request()->query())->map(fn($v) => is_null($v) ? '' : $v)->toArray()),
                            'show_info' => true,
                        ])
                    @endif
                </x-slot:footer>
            </x-resource-list-card>
</x-page-container>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                Tem certeza de que deseja excluir o produto <strong id="deleteProductName"></strong>?
                <br><small class="text-muted">Esta ação pode ser desfeita.</small>
            </div>
            <div class="modal-footer">
                <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <form id="deleteForm" action="#" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" label="Excluir" />
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="restoreModalLabel">Confirmar Restauração</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                Tem certeza de que deseja restaurar o produto <strong id="restoreProductName"></strong>?
                <br><small class="text-muted">O produto será restaurado e ficará disponível novamente.</small>
            </div>
            <div class="modal-footer">
                <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <form id="restoreForm" action="#" method="POST" class="d-inline">
                    @csrf
                    <x-button type="submit" variant="success" label="Restaurar" />
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="confirmAllProductsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Listar todos os produtos?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Você não aplicou filtros. Listar todos pode retornar muitos registros.</p>
            </div>
            <div class="modal-footer">
                <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <x-button type="button" class="btn-confirm-all-products" label="Listar todos" />
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/product.js') }}?v={{ time() }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        const form = document.getElementById('filtersFormProducts');

        if (!form || !startDate || !endDate) return;

        const parseDate = (str) => {
            if (!str) return null;
            const parts = str.split('/');
            if (parts.length === 3) {
                const d = new Date(parts[2], parts[1] - 1, parts[0]);
                return isNaN(d.getTime()) ? null : d;
            }
            return null;
        };

        const validateDates = () => {
            if (!startDate.value || !endDate.value) return true;

            const start = parseDate(startDate.value);
            const end = parseDate(endDate.value);

            if (start && end && start > end) {
                const message = 'A data inicial não pode ser maior que a data final.';
                if (window.easyAlert) {
                    window.easyAlert.warning(message);
                } else {
                    alert(message);
                }
                return false;
            }
            return true;
        };

        form.addEventListener('submit', function(e) {
            if (!validateDates()) {
                e.preventDefault();
                return;
            }

            if (startDate.value && !endDate.value) {
                e.preventDefault();
                const message = 'Para filtrar por período, informe as datas inicial e final.';
                if (window.easyAlert) {
                    window.easyAlert.error(message);
                } else {
                    alert(message);
                }
                endDate.focus();
            } else if (!startDate.value && endDate.value) {
                e.preventDefault();
                const message = 'Para filtrar por período, informe as datas inicial e final.';
                if (window.easyAlert) {
                    window.easyAlert.error(message);
                } else {
                    alert(message);
                }
                startDate.focus();
            }
        });
    });
</script>
@endpush
