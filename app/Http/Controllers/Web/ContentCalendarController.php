<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ContentAsset;
use App\Repositories\Contracts\ContentAssetRepositoryInterface;
use App\Services\Social\MetaPublisher;
use App\Services\Social\SocialPublisherInterface;
use App\Services\Social\TikTokPublisher;
use App\Services\Social\XPublisher;
use Illuminate\Http\Request;
use RuntimeException;

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
     * PRD F10's original manual-confirm flow: a human posted the
     * content manually elsewhere and confirms it here. Still the only
     * option for channels with no direct-publish integration (e.g. a
     * platform not yet connected, or TikTok's reply-less nature makes
     * no difference here since this is publish-side, not reply-side).
     */
    public function confirmPublished(Request $request, ContentAsset $asset)
    {
        $this->authorize('confirmPublished', $asset);

        $asset->confirmPublished();

        return back()->with('status', 'تم تأكيد النشر.');
    }

    /**
     * Social Media Hub epic: direct publish via the platform's own
     * API, for channels EQUIPER OS is actually connected to. Still
     * exclusively human-triggered by this controller action.
     */
    public function publishNow(Request $request, ContentAsset $asset)
    {
        $this->authorize('publish', $asset);

        $asset->publishNow($this->resolvePublisher($asset));

        return back()->with('status', 'تم النشر مباشرة على المنصة.');
    }

    private function resolvePublisher(ContentAsset $asset): SocialPublisherInterface
    {
        return match (true) {
            in_array($asset->channel, ['instagram_caption', 'facebook_post'], true) => new MetaPublisher(),
            $asset->channel === 'tiktok_video' => new TikTokPublisher(),
            $asset->channel === 'x_post' => new XPublisher(),
            default => throw new RuntimeException("No direct-publish integration for channel '{$asset->channel}' yet — use 'confirm published' after posting manually."),
        };
    }
}
