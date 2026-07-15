<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApprovalResource;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(
        private readonly ApprovalRepositoryInterface $approvals,
    ) {}

    public function index(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        $approvals = $request->boolean('decided')
            ? $this->approvals->recentlyDecidedForOrganization($organizationId)
            : $this->approvals->pendingForOrganization($organizationId);

        return ApprovalResource::collection($approvals);
    }
}
