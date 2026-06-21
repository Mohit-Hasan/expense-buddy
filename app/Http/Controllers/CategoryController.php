<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\TransactionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = TransactionCategory::query()
            ->withCount('transactions')
            ->orderBy('status')
            ->orderBy('type')
            ->orderBy('name');

        if ($request->filled('type') && in_array($request->input('type'), ['income', 'expense'], true)) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->input('status'));
        }

        return view('categories.index', [
            'categories' => $query->get(),
            'filters' => $request->only(['type', 'status']),
            'stats' => [
                'total' => TransactionCategory::query()->count(),
                'active' => TransactionCategory::query()->where('status', 'active')->count(),
                'archived' => TransactionCategory::query()->where('status', 'inactive')->count(),
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        TransactionCategory::query()->create([
            ...$request->validated(),
            'status' => 'active',
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function update(UpdateCategoryRequest $request, int $id): RedirectResponse
    {
        $category = TransactionCategory::query()->find($id);

        if ($category === null) {
            return back()->withErrors(['form' => 'Category not found.']);
        }

        $category->update($request->validated());

        $message = $category->status === 'inactive'
            ? 'Category archived successfully.'
            : 'Category updated successfully.';

        return redirect()
            ->route('categories.index')
            ->with('success', $message);
    }

    public function archive(int $id): RedirectResponse
    {
        $category = TransactionCategory::query()->find($id);

        if ($category === null) {
            return back()->withErrors(['form' => 'Category not found.']);
        }

        $category->update(['status' => 'inactive']);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category archived. Existing transactions keep this category.');
    }

    public function restore(int $id): RedirectResponse
    {
        $category = TransactionCategory::query()->find($id);

        if ($category === null) {
            return back()->withErrors(['form' => 'Category not found.']);
        }

        $category->update(['status' => 'active']);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category restored to active.');
    }
}
