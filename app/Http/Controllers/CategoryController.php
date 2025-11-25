<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Domain\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $service) {}

    public function index(Request $request): View
    {
        $filters = [
            'name' => $request->get('search'),
            'slug' => $request->get('search'),
            'is_active' => $request->get('is_active'),
            'order_by' => $request->get('sort_by', 'name'),
            'order_direction' => $request->get('sort_order', 'asc'),
        ];

        $perPage = (int) $request->get('per_page', 15);

        $paginator = $this->service->list($filters)->getData();
        if ($paginator instanceof \Illuminate\Database\Eloquent\Collection) {
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $paginator->forPage(\Illuminate\Support\Facades\Request::input('page', 1), $perPage),
                $paginator->count(),
                $perPage,
                (int) $request->get('page', 1)
            );
        }

        return view('pages.category.index', [ 'categories' => $paginator ]);
    }

    public function create(): View
    {
        return view('pages.category.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $result = $this->service->createCategory($validated);

        if ($result->isError()) {
            return back()->withInput()->with('error', $result->getMessage());
        }

        Log::info('Categoria criada', [ 'id' => $result->getData()->id ]);
        return redirect()->route('categories.index')->with('success', 'Categoria criada com sucesso');
    }

    public function show(string $slug): View
    {
        $result = $this->service->findBySlug($slug);
        abort_unless($result->isSuccess(), 404);
        return view('pages.category.show', [ 'category' => $result->getData() ]);
    }

    public function edit(int $id): View
    {
        $result = $this->service->findById($id);
        abort_unless($result->isSuccess(), 404);
        return view('pages.category.edit', [ 'category' => $result->getData() ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $result = $this->service->updateCategory($id, $validated);
        if ($result->isError()) {
            return back()->withInput()->with('error', $result->getMessage());
        }
        Log::info('Categoria atualizada', [ 'id' => $id ]);
        return redirect()->route('categories.index')->with('success', 'Categoria atualizada');
    }

    public function destroy(int $id): RedirectResponse
    {
        $result = $this->service->deleteCategory($id);
        if ($result->isError()) {
            return back()->with('error', $result->getMessage());
        }
        Log::info('Categoria excluída', [ 'id' => $id ]);
        return redirect()->route('categories.index')->with('success', 'Categoria excluída');
    }
}

