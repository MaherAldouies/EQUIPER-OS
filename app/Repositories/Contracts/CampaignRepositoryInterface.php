<?php

namespace App\Repositories\Contracts;

use App\Models\Campaign;
use Illuminate\Support\Collection;

interface CampaignRepositoryInterface
{
    public function forOrganization(string $organizationId): Collection;

    public function findOrFail(string $id): Campaign;
}
