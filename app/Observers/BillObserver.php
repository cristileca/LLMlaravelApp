<?php

namespace App\Observers;

use Exception;
use App\Models\Bill;
use App\Jobs\ProcessBill;
use App\Jobs\AnalyzeBill;
use Illuminate\Support\Facades\Log;

class BillObserver
{
    public function created(Bill $bill): void
    {
        ProcessBill::dispatch($bill);
    }
    /**
     * Handle the Bill "updated" event.
     */
    public function updated(Bill $bill): void
    {
        //
    }

    /**
     * Handle the Bill "deleted" event.
     */
    public function deleted(Bill $bill): void
    {
        //
    }

    /**
     * Handle the Bill "restored" event.
     */
    public function restored(Bill $bill): void
    {
        //
    }

    /**
     * Handle the Bill "force deleted" event.
     */
    public function forceDeleted(Bill $bill): void
    {
        //
    }

    /**
     * Handle the Bill "reanalyze" event.
     */
    public function reanalyze(Bill $bill): void
    {
        try {
            Log::info("Reanalysing bill with ID: {$bill->id}");
            AnalyzeBill::dispatch($bill);
        } catch (Exception $e) {
            Log::error("Failed to reanalyse bill: {$e->getMessage()}");
        }
    }
}
