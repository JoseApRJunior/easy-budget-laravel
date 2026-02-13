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
<x-layout.page-container>
    <x-layout.page-header
        title="Produtos"
        icon="box-seam"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Produtos' => route('provider.products.dashboard'),
            'Lista' => '#'
        ]">
        <p class="text-muted mb-0">Gerencie seu catálogo de produtos, preços e estoque</p>
    </x-layout.page-header>

    <x-form.filter-form :route="route('provider.products.index')" id="filtersFormProducts" :filters="$filters">
        <x-layout.grid-row>
            <x-form.filter-field
                col="col-md-4"
                name="search"
                label="Buscar"
                placeholder="Nome, SKU ou Descrição"
                :filters="$filters"
            />

            <x-form.filter-field
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
            </x-form.filter-field>

            <x-form.filter-field
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
            </x-form.filter-field>

            <x-form.filter-field
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
            </x-form.filter-field>

            <x-form.filter-field
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
            </x-form.filter-field>
        </x-layout.grid-row>

        <x-layout.grid-row class="mt-2">
            <x-form.filter-field
                col="col-md-3"
                name="min_price"
                label="Preço Mínimo"
                placeholder="0,00"
                class="currency-brl"
                inputmode="decimal"
                prefix="R$"
                :filters="$filters"
            />

            <x-form.filter-field
                col="col-md-3"
                name="max_price"
                label="Preço Máximo"
                placeholder="0,00"
                class="currency-brl"
                inputmode="decimal"
                prefix="R$"
                :filters="$filters"
            />

            <x-form.filter-field
                type="date"
                col="col-md-3"
                name="start_date"
                label="Cadastro Inicial"
                :filters="$filters"
            />

            <x-form.filter-field
                type="date"
                col="col-md-3"
                name="end_date"
                label="Cadastro Final"
                :filters="$filters"
            />
        </x-layout.grid-row>
    </x-form.filter-form>

    <x-resource.resource-list-card
        title="Catálogo de Produtos"
        mobileTitle="Produtos"
        icon="list-ul"
        :total="$products instanceof \Illuminate\Pagination\LengthAwarePaginator ? $products->total() : count($products)"
    >
        <x-slot:headerActions>
            <x-resource.table-header-actions
                resource="products"
                :filters="$filters"
                createLabel="Novo Produto"
                feature="products"
            />
        </x-slot:headerActions>

        <x-slot:desktop>
            <x-resource.resource-table>
                <x-slot:thead>
                    <tr>
                        <th style="width: 60px;">Imagem</th>
                        <th>Produto</th>
                        <th>SKU</th>
                        <th>Categoria</th>
                        <th class="text-end">Preço</th>
                        <th class="text-center">Margem</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: 150px;">Ações</th>
                    </tr>
                </x-slot:thead>

                <x-slot:tbody>
                    @forelse($products as $product)
                        <tr>
                            <td class="text-center">
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                    class="img-thumbnail rounded shadow-sm"
                                    style="width: 45px; height: 45px; object-fit: cover; background: #f8f9fa;">
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $product->name }}</div>
                                @if($product->deleted_at)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle smaller px-1">Deletado</span>
                                @endif
                            </td>
                            <td><code class="text-primary small">{{ $product->sku }}</code></td>
                            <td>
                                <span class="badge bg-light text-muted border fw-normal">
                                    {{ $product->category->name ?? 'Sem Categoria' }}
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark">{{ $product->formatted_price }}</td>
                            <td class="text-center">
                                @if($product->cost_price > 0)
                                    <span class="badge bg-{{ $product->margin_variant }}-api-subtle text-{{ $product->margin_variant }} border border-{{ $product->margin_variant }}-subtle px-2">
                                        {{ number_format($product->profit_margin_percentage, 1, ',', '.') }}%
                                    </span>
                                @else
                                    <span class="text-muted small italic">N/A</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <x-ui.status-badge :item="$product" statusField="active" activeLabel="Ativo" inactiveLabel="Inativo" />
                            </td>
                            <td class="text-center">
                                <x-resource.table-actions>
                                    @if ($product->deleted_at)
                                        <x-ui.button type="link" :href="route('provider.products.show', $product->sku)" variant="outline-info" size="sm" icon="eye" title="Visualizar" feature="products" />
                                        <x-ui.button variant="outline-success" size="sm" icon="arrow-counterclockwise"
                                            data-bs-toggle="modal" data-bs-target="#restoreModal"
                                            data-restore-url="{{ route('provider.products.restore', $product->sku) }}"
                                            data-product-name="{{ $product->name }}" title="Restaurar" feature="products" />
                                    @else
                                        <x-ui.button type="link" :href="route('provider.products.show', $product->sku)" variant="outline-info" size="sm" icon="eye" title="Visualizar" feature="products" />
                                        <x-ui.button type="link" :href="route('provider.products.edit', $product->sku)" variant="outline-primary" size="sm" icon="pencil-square" title="Editar" feature="products" />
                                        <x-ui.button variant="outline-danger" size="sm" icon="trash"
                                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                                            data-delete-url="{{ route('provider.products.destroy', $product->sku) }}"
                                            data-product-name="{{ $product->name }}" title="Excluir" feature="products" />
                                    @endif
                                </x-resource.table-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5 bg-light-subtle rounded-bottom">
                                <div class="py-4">
                                    <i class="bi bi-inbox mb-3 text-secondary-subtle d-block" style="font-size: 3.5rem;"></i>
                                    <h5 class="text-secondary fw-normal">Nenhum produto encontrado</h5>
                                    <p class="mb-0 small text-muted">Tente ajustar seus filtros ou cadastrar um novo produto.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-slot:tbody>
            </x-resource.resource-table>
        </x-slot:desktop>

        <x-slot:mobile>
            @forelse($products as $product)
                <x-resource.resource-mobile-item>
                    <x-slot:avatar>
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                            class="rounded shadow-sm border"
                            style="width: 48px; height: 48px; object-fit: cover; background: #f8f9fa;">
                    </x-slot:avatar>

                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="fw-bold text-dark">{{ $product->name }}</div>
                        <div class="fw-bold text-primary">{{ $product->formatted_price }}</div>
                    </div>

                    <x-slot:description>
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            <code class="text-primary small bg-light px-1 border rounded">{{ $product->sku }}</code>
                            <x-ui.status-badge :item="$product" statusField="active" activeLabel="Ativo" inactiveLabel="Inativo" />
                            @if($product->cost_price > 0)
                                <span class="badge bg-{{ $product->margin_variant }}-api-subtle text-{{ $product->margin_variant }} border border-{{ $product->margin_variant }}-subtle px-1">
                                    {{ number_format($product->profit_margin_percentage, 1, ',', '.') }}%
                                </span>
                            @endif
                        </div>
                        <div class="text-muted smaller">
                            <i class="bi bi-tag me-1"></i>{{ $product->category->name ?? 'Sem Categoria' }}
                        </div>
                    </x-slot:description>

                    <x-slot:actions>
                        <x-resource.table-actions mobile>
                            <x-ui.button type="link" :href="route('provider.products.show', $product->sku)" variant="outline-info" size="sm" icon="eye" title="Visualizar" feature="products" />
                            @if ($product->deleted_at)
                                <x-ui.button variant="outline-success" size="sm" icon="arrow-counterclockwise"
                                    data-bs-toggle="modal" data-bs-target="#restoreModal"
                                    data-restore-url="{{ route('provider.products.restore', $product->sku) }}"
                                    data-product-name="{{ $product->name }}" title="Restaurar" feature="products" />
                            @else
                                <x-ui.button type="link" :href="route('provider.products.edit', $product->sku)" variant="outline-primary" size="sm" icon="pencil-square" title="Editar" feature="products" />
                                <x-ui.button variant="outline-danger" size="sm" icon="trash"
                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                    data-delete-url="{{ route('provider.products.destroy', $product->sku) }}"
                                    data-product-name="{{ $product->name }}" title="Excluir" feature="products" />
                            @endif
                        </x-resource.table-actions>
                    </x-slot:actions>
                </x-resource.resource-mobile-item>
            @empty
                <div class="p-5 text-center text-muted bg-light-subtle">
                    <i class="bi bi-inbox mb-3 text-secondary-subtle d-block" style="font-size: 3rem;"></i>
                    <p class="mb-0">Nenhum produto encontrado.</p>
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
    </x-resource.resource-list-card>
</x-layout.page-container>

{{-- Modais de Exclusão e Restauração --}}
<x-ui.confirm-modal
    id="deleteModal"
    title="Confirmar Exclusão"
    message="Tem certeza de que deseja excluir o produto"
    confirm-label="Excluir"
    variant="danger"
    form-id="deleteForm"
    method="DELETE"
    item-name-id="deleteProductName"
    feature="products"
/>

<x-ui.confirm-modal
    id="restoreModal"
    title="Confirmar Restauração"
    message="Tem certeza de que deseja restaurar o produto"
    confirm-label="Restaurar"
    variant="success"
    form-id="restoreForm"
    item-name-id="restoreProductName"
    feature="products"
/>
@endsection
