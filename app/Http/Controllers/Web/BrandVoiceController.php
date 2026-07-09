<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BrandVoice;
use Illuminate\Http\Request;

class BrandVoiceController extends Controller
{
    public function edit(Request $request)
    {
        $organization = $request->attributes->get('organization');

        $active = BrandVoice::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->first();

        return view('content.brand-voice', compact('active'));
    }

    public function update(Request $request)
    {
        $organization = $request->attributes->get('organization');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tone_guidelines' => ['required', 'string'],
            'vocabulary_notes' => ['nullable', 'string'],
            'things_to_avoid' => ['nullable', 'string'],
            'brand_facts' => ['nullable', 'string'],
        ]);

        $brandVoice = BrandVoice::query()->create([
            'organization_id' => $organization->id,
            ...$data,
            'status' => 'draft',
            'authored_by' => $request->user()->id,
        ]);

        // Business rule: only one Active Brand Voice at a time.
        $brandVoice->activate();

        return back()->with('status', 'تم تحديث صوت العلامة التجارية وتفعيله.');
    }
}
