<?php

namespace App\Observers;

use Exception;
use Carbon\Carbon;
use Aws\S3\S3Client;
use GuzzleHttp\Client;
use Carbon\Traits\Date;
use App\Models\Contract;
use App\Jobs\ProcessContract;
use App\Services\ContractService;
use Illuminate\Support\Facades\Storage;



class ContractObserver
{
    public function created(Contract $contract): void
    {
        ProcessContract::dispatch($contract);
    }
    /**
     * Handle the Contract "updated" event.
     */
    public function updated(Contract $contract): void
    {
        //
    }

    /**
     * Handle the Contract "deleted" event.
     */
    public function deleted(Contract $contract): void
    {
        //
    }

    /**
     * Handle the Contract "restored" event.
     */
    public function restored(Contract $contract): void
    {
        //
    }

    /**
     * Handle the Contract "force deleted" event.
     */
    public function forceDeleted(Contract $contract): void
    {
        //
    }
}
