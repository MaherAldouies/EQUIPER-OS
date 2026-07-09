<?php

namespace App\Repositories\Eloquent;

use App\Models\ContentAsset;
use App\Repositories\Contracts\ContentAssetRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentContentAssetRepository implements ContentAssetRepositoryInterface
{
    public function approvedForOrganization(string $organizationId): Collection
    {
        return ContentAsset::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'approved')
            ->with('content.product')
            ->get();
    }

    public function scheduledForOrganization(string $organizationId): Collection
    {
        return ContentAsset::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'scheduled')
            ->with('content.product')
            ->orderBy('scheduled_for')
            ->get();
    }

    public function findOrFail(string $id): ContentAsset
    {
        return ContentAsset::query()->findOrFail($id);
    }
}
