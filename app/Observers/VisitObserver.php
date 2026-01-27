<?php

namespace App\Observers;

use App\Models\Visit;
use App\Events\VisitCompleted;
use Illuminate\Support\Facades\Log;

class VisitObserver
{
    /**
     * Handle the Visit "updated" event.
     */
    public function updated(Visit $visit): void
    {
        // Log all visitor status changes
        if ($visit->wasChanged('status')) {
            Log::info('Visit status changed', [
                'visit_id' => $visit->id,
                'old_status' => $visit->getOriginal('status'),
                'new_status' => $visit->status,
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Handle the Visit "deleted" event.
     */
    public function deleted(Visit $visit): void
    {
        Log::info('Visit deleted', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle the Visit "restored" event.
     */
    public function restored(Visit $visit): void
    {
        Log::info('Visit restored', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle the Visit "force deleted" event.
     */
    public function forceDeleted(Visit $visit): void
    {
        Log::info('Visit force deleted', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
        ]);
    }
}
