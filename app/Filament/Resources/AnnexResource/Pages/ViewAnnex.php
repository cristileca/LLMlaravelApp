<?php

namespace App\Filament\Resources\AnnexResource\Pages;

use App\Filament\Resources\AnnexResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewAnnex extends ViewRecord
{
    protected static string $resource = AnnexResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
