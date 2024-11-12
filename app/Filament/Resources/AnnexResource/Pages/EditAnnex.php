<?php

namespace App\Filament\Resources\AnnexResource\Pages;

use App\Filament\Resources\AnnexResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnnex extends EditRecord
{
    protected static string $resource = AnnexResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
