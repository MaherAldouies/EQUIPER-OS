<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => $this->price,
            'brand_name' => $this->brand_name,
            'is_agency_brand' => $this->is_agency_brand,
            'lifecycle_state' => $this->lifecycle_state,
            'stock_quantity' => $this->stock_quantity,
            'stock_status' => $this->stock_status,
            'salla_category_name' => $this->salla_category_name,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'enriched_at' => $this->enriched_at,
            'last_synced_at' => $this->last_synced_at,
        ];
    }
}
