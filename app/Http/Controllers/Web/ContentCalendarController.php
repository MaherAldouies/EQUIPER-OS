<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ContentAsset;
use App\Repositories\Contracts\ContentAssetRepositoryInterface;
use Illuminate\Http\Request;

class ContentCalendarController extends Controller
{
    public function __construct(
        private readonly ContentAssetRepositoryInterface $contentAssets,
    ) {}

    public function index(Request $request)
    {
        $organization = $request->attributes->get('organization');

        $approved = $this->contentAssets->approvedForOrganization($organization->id);
        $scheduled = $this->contentAssets->scheduledForOrganization($organization->id);

        return view('content.calendar', compact('approved', 'scheduled'));
    }

    public function schedule(Request $request, ContentAsset $asset)
    {
        $this->authorize('schedule', $asset);

        $data = $request->validate(['scheduled_for' => ['required', 'date', 'after:now']]);

        $asset->schedule(new \DateTime($data['scheduled_for']));

        return back()->with('status', 'تمت جدولة المحتوى.');
    }

    /**
     * v1.0 uses manual-confirm publishing (PRD F10) — no direct social
     * platform API publishing yet.
     */
    public function confirmPublished(Request $request, ContentAsset $asset)
    {
        $this->authorize('confirmPublished', $asset);

        $asset->confirmPublished();

        return back()->with('status', 'تم تأكيد النشر.');
    }
}
