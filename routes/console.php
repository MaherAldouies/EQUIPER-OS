<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled Jobs
|--------------------------------------------------------------------------
| These correspond directly to PRD acceptance criteria — do not change
| the cadence below without updating the PRD's F3/F12 acceptance criteria
| to match.
*/

// F3: "a scheduled reconciliation job (every 30 minutes) as a fallback
// for missed webhooks" — the actual polling logic (calling Salla's API
// and looping SallaSyncService::syncProduct()) is Sprint 1 scope, not
// yet implemented in this Sprint 0 scaffold.
Schedule::command('salla:reconcile')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// F9: "Data refreshes at least every 30 minutes."
Schedule::command('dashboard:refresh')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// Note: events:relay and events:consume-product-enriched are NOT
// scheduled here — per the Infrastructure Architecture document,
// Section 3, these run as continuous long-running worker processes
// (e.g. under Supervisor), not as periodic cron-style jobs, to meet
// the "delivery within 5 seconds" acceptance criteria in PRD F1.
