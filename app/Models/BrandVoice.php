<?php

namespace App\Models;

use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * BrandVoice — Business Ontology, Knowledge Domain.
 * Business rule: "Only one Brand Voice definition may be 'Active' at
 * a time — no competing/ambiguous versions in play simultaneously."
 */
class BrandVoice extends Model
{
    use HasDomainEvents, HasFactory, HasUuidPrimaryKey;

    protected $fillable = [
        'organization_id', 'title', 'tone_guidelines', 'vocabulary_notes',
        'things_to_avoid', 'brand_facts', 'status', 'authored_by',
    ];

    public function activate(): void
    {
        DB::transaction(function () {
            static::query()
                ->where('organization_id', $this->organization_id)
                ->where('status', 'active')
                ->update(['status' => 'superseded']);

            $this->forceFill(['status' => 'active'])->save();

            $this->recordEvent(eventType: 'BrandVoiceUpdated', payload: [
                'brand_voice_id' => $this->id,
            ]);
        });
    }
}
