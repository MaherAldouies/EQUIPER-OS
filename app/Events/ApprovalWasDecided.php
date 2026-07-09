<?php

namespace App\Events;

use App\Models\Approval;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalWasDecided
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Approval $approval,
    ) {}
}
