<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'goal' => $this->goal,
            'utm_campaign_slug' => $this->utm_campaign_slug,
            'status' => $this->status,
            'content_assets_count' => $this->whenCounted('contentAssets'),
            'created_at' => $this->created_at,
        ];
    }
}
