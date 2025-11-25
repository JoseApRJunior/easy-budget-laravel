<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Controllers\Traits\HandlesCategoryContext;
use App\Services\Domain\CategoryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use HandlesCategoryContext;
    public function __construct(private CategoryService $service) {}

    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 15);

        $collection = $this->service->listAll()->getData();
        if ($collection instanceof \Illuminate\Database\Eloquent\Collection) {
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $collection->forPage(\Illuminate\Support\Facades\Request::input('page', 1), $perPage),
                $collection->count(),
                $perPage,
                (int) $request->get('page', 1)
            );
        } else {
            $paginator = $collection;
        }

        return view($this->categoryView('index'), ['categories' => $paginator]);
    }

    public function create(): View
    {
        return view($this->categoryView('create'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $result = $this->service->createCategory($validated);

        if ($result->isError()) {
            return back()->withInput()->with('error', $result->getMessage());
        }

        Log::info('Categoria criada', ['id' => $result->getData()->id]);
        $route = $this->isAdminContext() ? 'admin.categories.index' : 'categories.index';
        return redirect()->route($route)->with('success', 'Categoria criada com sucesso');
    }

    public function show(string $slug): View
    {
        $result = $this->service->findBySlug($slug);
        abort_unless($result->isSuccess(), 404);
        return view($this->categoryView('show'), ['category' => $result->getData()]);
    }

    public function edit(int $id): View
    {
        $result = $this->service->findById($id);
        abort_unless($result->isSuccess(), 404);
        return view($this->categoryView('edit'), ['category' => $result->getData()]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $result = $this->service->updateCategory($id, $validated);
        if ($result->isError()) {
            return back()->withInput()->with('error', $result->getMessage());
        }
        Log::info('Categoria atualizada', ['id' => $id]);
        $route = $this->isAdminContext() ? 'admin.categories.index' : 'categories.index';
        return redirect()->route($route)->with('success', 'Categoria atualizada');
    }

    public function destroy(int $id): RedirectResponse
    {
        $result = $this->service->deleteCategory($id);
        if ($result->isError()) {
            return back()->with('error', $result->getMessage());
        }
        Log::info('Categoria excluída', ['id' => $id]);
        $route = $this->isAdminContext() ? 'admin.categories.index' : 'categories.index';
        return redirect()->route($route)->with('success', 'Categoria excluída');
    }
}
