<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface
{
    public function paginateForOrganization(string $organizationId, bool $onlyMiscategorized = false, int $perPage = 50): LengthAwarePaginator;

    public function findOrFail(string $id): Product;

    public function findBySallaId(string $organizationId, string $sallaProductId): ?Product;

    public function activeCategories(string $organizationId): Collection;

    public function bulkAssignCategory(array $productIds, string $categoryId): int;
}
