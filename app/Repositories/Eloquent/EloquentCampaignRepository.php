<?php

namespace App\Repositories\Eloquent;

use App\Models\Campaign;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentCampaignRepository implements CampaignRepositoryInterface
{
    public function forOrganization(string $organizationId): Collection
    {
        return Campaign::query()
            ->where('organization_id', $organizationId)
            ->withCount('contentAssets')
            ->latest()
            ->get();
    }

    public function findOrFail(string $id): Campaign
    {
        return Campaign::query()->findOrFail($id);
    }
}
