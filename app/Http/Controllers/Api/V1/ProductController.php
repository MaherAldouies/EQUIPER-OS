<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function index(Request $request)
    {
        $products = $this->products->paginateForOrganization(
            organizationId: $request->user()->organization_id,
            onlyMiscategorized: $request->boolean('miscategorized'),
        );

        return ProductResource::collection($products);
    }

    public function show(Request $request, string $product)
    {
        $found = $this->products->findOrFail($product);

        abort_unless($found->organization_id === $request->user()->organization_id, 404);

        return new ProductResource($found);
    }
}
