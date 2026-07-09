<?php

namespace App\Repositories\Contracts;

use App\Models\Approval;
use Illuminate\Support\Collection;

interface ApprovalRepositoryInterface
{
    public function pendingForOrganization(string $organizationId): Collection;

    public function recentlyDecidedForOrganization(string $organizationId, int $limit = 20): Collection;

    public function findOrFail(string $id): Approval;
}
