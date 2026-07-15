<?php

namespace App\Livewire\Approvals;

use App\Models\Approval;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Approvals\Queue — PRD F7: the human-in-the-loop safety gate. Calls
 * Approval::approve()/reject() directly (the same model methods
 * ContentApprovalController already calls) so the business rule stays
 * in exactly one place.
 */
#[Layout('layouts.app')]
class Queue extends Component
{
    public ?string $rejectingId = null;

    public string $reason = '';

    public function approve(string $approvalId): void
    {
        $approval = Approval::query()->findOrFail($approvalId);
        Gate::authorize('decide', $approval);

        $approval->approve(auth()->user());

        session()->flash('status', 'تم الاعتماد بنجاح.');
    }

    public function startReject(string $approvalId): void
    {
        $this->rejectingId = $approvalId;
        $this->reason = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
        $this->reason = '';
    }

    public function reject(string $approvalId): void
    {
        $approval = Approval::query()->findOrFail($approvalId);
        Gate::authorize('decide', $approval);

        $this->validate(['reason' => ['required', 'string', 'max:1000']]);

        $approval->reject(auth()->user(), $this->reason);

        $this->rejectingId = null;
        $this->reason = '';

        session()->flash('status', 'تم الرفض وتسجيل السبب.');
    }

    public function render(ApprovalRepositoryInterface $approvals)
    {
        $organizationId = auth()->user()->organization_id;

        return view('livewire.approvals.queue', [
            'pending' => $approvals->pendingForOrganization($organizationId),
            'decided' => $approvals->recentlyDecidedForOrganization($organizationId),
        ]);
    }
}
