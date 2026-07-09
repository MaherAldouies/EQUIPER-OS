<?php

namespace App\Repositories\Contracts;

use App\Models\ContentAsset;
use Illuminate\Support\Collection;

interface ContentAssetRepositoryInterface
{
    public function approvedForOrganization(string $organizationId): Collection;

    public function scheduledForOrganization(string $organizationId): Collection;

    public function findOrFail(string $id): ContentAsset;
}
