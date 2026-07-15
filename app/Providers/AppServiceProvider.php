<?php

namespace App\Providers;

use App\Repositories\Contracts\ApprovalRepositoryInterface;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use App\Repositories\Contracts\ContentAssetRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Eloquent\EloquentApprovalRepository;
use App\Repositories\Eloquent\EloquentCampaignRepository;
use App\Repositories\Eloquent\EloquentContentAssetRepository;
use App\Repositories\Eloquent\EloquentProductRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(ApprovalRepositoryInterface::class, EloquentApprovalRepository::class);
        $this->app->bind(ContentAssetRepositoryInterface::class, EloquentContentAssetRepository::class);
        $this->app->bind(CampaignRepositoryInterface::class, EloquentCampaignRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Lets @can('product.view') / @can('dashboard.view_revenue') in
        // Blade resolve against User::hasPermission() (the Ontology's
        // Permission entity) rather than only against model Policies.
        // Returning null (not false) for non-dotted ability names falls
        // through to normal Policy resolution — see e.g. ProductPolicy's
        // 'enrich' ability, which still goes through its own class.
        Gate::before(function ($user, string $ability) {
            return str_contains($ability, '.') ? ($user->hasPermission($ability) ?: null) : null;
        });
    }
}
