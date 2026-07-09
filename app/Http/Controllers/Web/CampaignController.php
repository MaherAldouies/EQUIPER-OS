<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaigns,
    ) {}

    public function index(Request $request)
    {
        $organization = $request->attributes->get('organization');

        $campaigns = $this->campaigns->forOrganization($organization->id);

        return view('campaigns.index', compact('campaigns'));
    }

    public function store(Request $request)
    {
        $organization = $request->attributes->get('organization');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'goal' => ['nullable', 'string', 'max:1000'],
        ]);

        Campaign::createNew([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'goal' => $data['goal'] ?? null,
            'utm_campaign_slug' => Str::slug($data['name']).'-'.now()->format('Ymd'),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'تم إنشاء الحملة.');
    }

    public function complete(Request $request, Campaign $campaign)
    {
        $this->authorize('complete', $campaign);

        // Business rule enforced again inside the model itself (defense
        // in depth): cannot complete while scheduled-but-unpublished
        // assets remain linked.
        $campaign->complete();

        return back()->with('status', 'تم إغلاق الحملة.');
    }
}
