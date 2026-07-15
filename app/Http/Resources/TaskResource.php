<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'assigned_to' => $this->assigned_to,
            'related_approval_id' => $this->related_approval_id,
            'status' => $this->status,
            'due_at' => $this->due_at,
            'completed_at' => $this->completed_at,
        ];
    }
}
