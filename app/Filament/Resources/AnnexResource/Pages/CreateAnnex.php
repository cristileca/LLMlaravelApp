<?php

namespace App\Filament\Resources\AnnexResource\Pages;

use App\Filament\Resources\AnnexResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnex extends CreateRecord
{
    protected static string $resource = AnnexResource::class;

    public $contractId = null;

    public function mount(): void
    {
        $this->contractId = request()->input('contract_id');

        if ($this->contractId) {
            $this->form->fill([
                'contract_id' => $this->contractId,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }
}
