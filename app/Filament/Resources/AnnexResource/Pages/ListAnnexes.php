<?php

namespace App\Filament\Resources\AnnexResource\Pages;

use App\Filament\Resources\AnnexResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnnexes extends ListRecords
{
    protected static string $resource = AnnexResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
