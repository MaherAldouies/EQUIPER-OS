<?php

namespace Database\Seeders;

use App\Models\EventCatalogEntry;
use Illuminate\Database\Seeder;

/**
 * Registers every event type used by v1.0 features, per the Event
 * Catalog governance rule (Event-Driven Architecture doc, Section 3.3).
 * Extend this file — never publish an event type without adding it here
 * first.
 */
class EventCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            // F3 — Salla Integration
            ['event_type' => 'SallaProductSynced', 'aggregate_type' => 'Product', 'owning_domain' => 'Product Domain', 'description' => 'A product was newly synced from Salla.'],
            ['event_type' => 'StockLevelChanged', 'aggregate_type' => 'Product', 'owning_domain' => 'Product Domain', 'description' => 'A synced product\'s stock level changed.'],
            ['event_type' => 'SallaOrderSynced', 'aggregate_type' => 'Order', 'owning_domain' => 'Commerce Domain', 'description' => 'An order was synced (created or updated) from Salla.'],

            // F4 — Product Module
            ['event_type' => 'ProductEnriched', 'aggregate_type' => 'Product', 'owning_domain' => 'Product Domain', 'description' => 'A product was enriched with a corrected category and description; triggers AI generation.'],
            ['event_type' => 'CategoryRestructured', 'aggregate_type' => 'Category', 'owning_domain' => 'Product Domain', 'description' => 'Category taxonomy structure changed.'],

            // F5 — Brand Voice
            ['event_type' => 'BrandVoiceUpdated', 'aggregate_type' => 'BrandVoice', 'owning_domain' => 'Knowledge Domain', 'description' => 'A Brand Voice document became active.'],

            // F6 — AI Content & SEO Generation
            ['event_type' => 'ContentDrafted', 'aggregate_type' => 'Content', 'owning_domain' => 'Content Domain', 'description' => 'AI drafted a Content object.', 'requires_approval_downstream' => true],
            ['event_type' => 'SEOAssetGenerated', 'aggregate_type' => 'SeoAsset', 'owning_domain' => 'SEO Domain', 'description' => 'AI generated an SEO asset.', 'requires_approval_downstream' => true],

            // F7 — Approval Workflow
            ['event_type' => 'ApprovalRequested', 'aggregate_type' => 'Approval', 'owning_domain' => 'Workflow Domain', 'description' => 'An entity requires human approval before publish.'],
            ['event_type' => 'ApprovalGranted', 'aggregate_type' => 'Approval', 'owning_domain' => 'Workflow Domain', 'description' => 'A human approved a pending Approval.'],
            ['event_type' => 'ApprovalRejected', 'aggregate_type' => 'Approval', 'owning_domain' => 'Workflow Domain', 'description' => 'A human rejected a pending Approval.'],

            // F10 — Content Calendar
            ['event_type' => 'AssetScheduled', 'aggregate_type' => 'ContentAsset', 'owning_domain' => 'Content Domain', 'description' => 'A Content Asset was scheduled for publishing.'],
            ['event_type' => 'AssetPublished', 'aggregate_type' => 'ContentAsset', 'owning_domain' => 'Content Domain', 'description' => 'A Content Asset was manually confirmed published.'],

            // F11 — Campaign Tracking
            ['event_type' => 'CampaignCreated', 'aggregate_type' => 'Campaign', 'owning_domain' => 'Marketing Domain', 'description' => 'A new Campaign was created.'],
            ['event_type' => 'CampaignCompleted', 'aggregate_type' => 'Campaign', 'owning_domain' => 'Marketing Domain', 'description' => 'A Campaign was marked completed.'],

            // F12 — Integration Monitoring
            ['event_type' => 'IntegrationDegraded', 'aggregate_type' => 'Integration', 'owning_domain' => 'Administration Domain', 'description' => 'An external integration entered a degraded/error state.'],

            // F13 — Task Management
            ['event_type' => 'TaskCreated', 'aggregate_type' => 'Task', 'owning_domain' => 'Workflow Domain', 'description' => 'A Task was created, often from an Approval request.'],
            ['event_type' => 'TaskCompleted', 'aggregate_type' => 'Task', 'owning_domain' => 'Workflow Domain', 'description' => 'A Task was completed.'],

            // F9 — Analytics
            ['event_type' => 'SignalDetected', 'aggregate_type' => 'AnalyticsSignal', 'owning_domain' => 'Analytics Domain', 'description' => 'A normalized analytics signal was derived from raw activity.'],

            // F14 — Simple Automation Rule (P2)
            ['event_type' => 'AutomationTriggered', 'aggregate_type' => 'Task', 'owning_domain' => 'Workflow Domain', 'description' => 'A deterministic Automation Rule fired (e.g. low-stock alert).'],

            // F2 — Identity, Roles & Permissions
            ['event_type' => 'TeamMemberActivated', 'aggregate_type' => 'User', 'owning_domain' => 'Administration Domain', 'description' => 'A Team Member was invited/activated.'],

            // Social Media Hub — unified inbox + direct publish
            ['event_type' => 'SocialMessageReceived', 'aggregate_type' => 'SocialMessage', 'owning_domain' => 'Marketing Domain', 'description' => 'An inbound message/comment arrived from a connected social platform.'],
            ['event_type' => 'SocialMessageReplied', 'aggregate_type' => 'SocialMessage', 'owning_domain' => 'Marketing Domain', 'description' => 'A human sent a reply to a social message from the unified inbox.'],
        ];

        foreach ($events as $event) {
            EventCatalogEntry::query()->updateOrCreate(
                ['event_type' => $event['event_type']],
                $event
            );
        }
    }
}
