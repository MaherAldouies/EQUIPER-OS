<?php

namespace App\Repositories\Eloquent;

use App\Models\Approval;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentApprovalRepository implements ApprovalRepositoryInterface
{
    public function pendingForOrganization(string $organizationId): Collection
    {
        return Approval::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'pending')
            ->with('approvable')
            ->orderBy('created_at')
            ->get();
    }

    public function recentlyDecidedForOrganization(string $organizationId, int $limit = 20): Collection
    {
        return Approval::query()
            ->where('organization_id', $organizationId)
            ->whereIn('status', ['approved', 'rejected'])
            ->with('approvable')
            ->latest('decided_at')
            ->limit($limit)
            ->get();
    }

    public function findOrFail(string $id): Approval
    {
        return Approval::query()->findOrFail($id);
    }
}
