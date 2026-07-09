<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use Illuminate\Http\Request;

class ContentApprovalController extends Controller
{
    public function __construct(
        private readonly ApprovalRepositoryInterface $approvals,
    ) {}

    public function index(Request $request)
    {
        $organization = $request->attributes->get('organization');

        $pending = $this->approvals->pendingForOrganization($organization->id);
        $decided = $this->approvals->recentlyDecidedForOrganization($organization->id);

        return view('content.approvals', compact('pending', 'decided'));
    }

    public function approve(Request $request, Approval $approval)
    {
        $this->authorize('decide', $approval);

        $approval->approve($request->user());

        return back()->with('status', 'تم الاعتماد بنجاح.');
    }

    public function reject(Request $request, Approval $approval)
    {
        $this->authorize('decide', $approval);

        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $approval->reject($request->user(), $data['reason']);

        return back()->with('status', 'تم الرفض وتسجيل السبب.');
    }
}
