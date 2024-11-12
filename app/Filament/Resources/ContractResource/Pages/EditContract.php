<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Filament\Notifications\Notification;

class EditContract extends EditRecord
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function ($record) {
                    try {
                        $record->delete();
                    } catch (QueryException $e) {
                        if ($e->getCode() === '23000') {
                            Notification::make()
                                ->title('Imposibil de șters contractul!')
                                ->body('Acest contract nu poate fi șters deoarece este legat de una sau mai multe facturi.')
                                ->danger()
                                ->send();
                        } else {
                            throw $e; // Re-throw other exceptions
                        }
                    }
                })
        ];
    }
}
