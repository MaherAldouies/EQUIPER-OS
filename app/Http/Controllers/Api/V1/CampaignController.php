<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaigns,
    ) {}

    public function index(Request $request)
    {
        // Repository already eager-loads contentAssets count.
        $campaigns = $this->campaigns->forOrganization($request->user()->organization_id);

        return CampaignResource::collection($campaigns);
    }
}
