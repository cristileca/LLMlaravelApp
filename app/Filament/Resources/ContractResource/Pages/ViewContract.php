<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Filament\Resources\AnnexResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Illuminate\Database\QueryException;
use Filament\Notifications\Notification;

class ViewContract extends ViewRecord
{
    protected static string $resource = ContractResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\DeleteAction::make()
                ->action(function ($record) {
                    try {
                        $record->delete();
                    } catch (QueryException $e) {
                        if ($e->getCode() === '23000') { // Foreign key constraint violation
                            Notification::make()
                                ->title('Imposibil de șters contractul!')
                                ->body('Acest contract nu poate fi șters deoarece este legat de una sau mai multe facturi.')
                                ->danger()
                                ->send();
                        } else {
                            throw $e; // Re-throw other exceptions
                        }
                    }
                }),
            Actions\Action::make('createAnnex')
                ->label('Adaugă Anexă')
                ->url(fn() => $this->record->id
                    ? AnnexResource::getUrl('create', ['contract_id' => $this->record->id])
                    : AnnexResource::getUrl('create'))
                ->requiresConfirmation()
                ->color('warning'),
        ];
    }
}
