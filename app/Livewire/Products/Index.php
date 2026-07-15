<?php

namespace App\Livewire\Products;

use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Products\Index — PRD F4: product list + bulk re-categorization
 * (resolves the known 262-product miscategorization issue in one
 * workflow). Reuses ProductRepositoryInterface directly rather than
 * duplicating query logic already written for ProductController.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public bool $onlyMiscategorized = false;

    /** @var array<string> */
    public array $selected = [];

    public string $bulkCategoryId = '';

    public function toggleMiscategorized(): void
    {
        $this->onlyMiscategorized = ! $this->onlyMiscategorized;
        $this->selected = [];
        $this->resetPage();
    }

    public function bulkRecategorize(ProductRepositoryInterface $products): void
    {
        Gate::authorize('product.manage_category');

        $this->validate([
            'selected' => ['required', 'array', 'min:1'],
            'bulkCategoryId' => ['required', 'uuid', 'exists:categories,id'],
        ]);

        $count = $products->bulkAssignCategory($this->selected, $this->bulkCategoryId);

        $this->selected = [];
        $this->bulkCategoryId = '';

        session()->flash('status', "{$count} منتج تم تصنيفه بنجاح.");
    }

    public function render(ProductRepositoryInterface $products)
    {
        $organization = auth()->user()->organization;

        return view('livewire.products.index', [
            'products' => $products->paginateForOrganization(
                organizationId: $organization->id,
                onlyMiscategorized: $this->onlyMiscategorized,
            ),
            'categories' => $products->activeCategories($organization->id),
        ]);
    }
}
