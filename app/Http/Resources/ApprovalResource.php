<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'approvable_type' => class_basename($this->approvable_type),
            'approvable_id' => $this->approvable_id,
            'status' => $this->status,
            'requested_for_role' => $this->requested_for_role,
            'rejection_reason' => $this->rejection_reason,
            'expires_at' => $this->expires_at,
            'decided_at' => $this->decided_at,
            'created_at' => $this->created_at,
        ];
    }
}
