<?php

namespace App\Observers;

use App\Models\Annex;
use App\Jobs\ProcessAnnex;

class AnnexObserver
{
    public function created(Annex $annex)
    {
        ProcessAnnex::dispatch($annex);
    }
    /**
     * Handle the Annex "updated" event.
     */
    public function updated(Annex $annex): void
    {
        //
    }

    /**
     * Handle the Annex "deleted" event.
     */
    public function deleted(Annex $annex): void
    {
        //
    }

    /**
     * Handle the Annex "restored" event.
     */
    public function restored(Annex $annex): void
    {
        //
    }

    /**
     * Handle the Annex "force deleted" event.
     */
    public function forceDeleted(Annex $annex): void
    {
        //
    }
}
