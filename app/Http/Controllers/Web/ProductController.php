<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function index(Request $request)
    {
        $organization = $request->attributes->get('organization');

        $products = $this->products->paginateForOrganization(
            organizationId: $organization->id,
            onlyMiscategorized: $request->boolean('miscategorized'),
        );

        $categories = $this->products->activeCategories($organization->id);

        return view('products.index', compact('products', 'categories'));
    }

    /**
     * PRD F4 acceptance criteria: "Bulk re-categorization is possible
     * (select multiple products, assign correct category) to resolve
     * the known 262-product miscategorization in one workflow."
     * Coarse-grained authorization here (CheckPermission middleware,
     * routes/web.php) — there is no single Product instance to run the
     * ProductPolicy against for a bulk action.
     */
    public function bulkRecategorize(Request $request)
    {
        $data = $request->validate([
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['uuid', 'exists:products,id'],
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
        ]);

        $count = $this->products->bulkAssignCategory($data['product_ids'], $data['category_id']);

        return back()->with('status', "{$count} منتج تم تصنيفه بنجاح.");
    }

    /**
     * Business rule: "A Product cannot be marked 'Enriched' without a
     * corrected Category assignment" — enforced inside
     * Product::markEnriched(); ProductPolicy::enrich() additionally
     * enforces that only draft products with a category assigned are
     * eligible, at the per-object level.
     */
    public function enrich(Request $request, Product $product)
    {
        $this->authorize('enrich', $product);

        $product->markEnriched();

        return back()->with('status', "تم إثراء المنتج \"{$product->name}\" — سيبدأ الذكاء الاصطناعي بصياغة المحتوى تلقائيًا.");
    }
}
