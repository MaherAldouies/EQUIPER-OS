<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * ProductPolicy — fine-grained, object-state-aware authorization.
 * Complements (does not replace) the coarse Role/Permission middleware
 * check: CheckPermission enforces "can this Role ever manage
 * categories," while this Policy enforces "can THIS user act on THIS
 * specific Product right now" (e.g. state-dependent rules).
 */
class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $user->organization_id === $product->organization_id
            && $user->hasPermission('product.view');
    }

    public function manageCategory(User $user, Product $product): bool
    {
        return $user->organization_id === $product->organization_id
            && $user->hasPermission('product.manage_category');
    }

    /**
     * Object-state rule beyond what the Product model's own
     * markEnriched() enforces: only draft products can be (re)enriched
     * through this action — an already-enriched product should go
     * through a distinct "re-enrich" flow (v1.1+) rather than silently
     * re-triggering AI generation via the same action.
     */
    public function enrich(User $user, Product $product): bool
    {
        return $this->manageCategory($user, $product)
            && $product->lifecycle_state === 'draft'
            && $product->category_id !== null;
    }
}
