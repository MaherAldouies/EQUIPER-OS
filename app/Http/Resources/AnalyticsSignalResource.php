<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsSignalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'metric_key' => $this->metric_key,
            'value' => $this->value,
            'unit' => $this->unit,
            'source' => $this->source,
            'confidence' => $this->confidence,
            'is_reliable' => $this->isReliable(),
            'signal_date' => $this->signal_date,
        ];
    }
}
