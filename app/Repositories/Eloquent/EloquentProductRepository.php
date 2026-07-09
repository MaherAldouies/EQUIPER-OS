<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function paginateForOrganization(string $organizationId, bool $onlyMiscategorized = false, int $perPage = 50): LengthAwarePaginator
    {
        return Product::query()
            ->where('organization_id', $organizationId)
            ->with('category')
            ->when($onlyMiscategorized, function ($query) {
                $query->whereColumn('salla_category_name', '!=', 'name')
                    ->orWhereNull('category_id');
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findOrFail(string $id): Product
    {
        return Product::query()->findOrFail($id);
    }

    public function findBySallaId(string $organizationId, string $sallaProductId): ?Product
    {
        return Product::query()
            ->where('organization_id', $organizationId)
            ->where('salla_product_id', $sallaProductId)
            ->first();
    }

    public function activeCategories(string $organizationId): Collection
    {
        return Category::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function bulkAssignCategory(array $productIds, string $categoryId): int
    {
        return Product::query()
            ->whereIn('id', $productIds)
            ->update(['category_id' => $categoryId]);
    }
}
