<?php

namespace App\Filament\Resources\BillResource\Pages;

use App\Filament\Resources\BillResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use App\Jobs\AnalyzeBill;
use Filament\Actions\Action;

class ViewBill extends ViewRecord
{
    protected static string $resource = BillResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('reanalyze')
                ->label('Reanalyze')
                ->color('info')
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->reanalyze()),

            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    protected function reanalyze()
    {
        $bill = $this->record;

        AnalyzeBill::dispatch($bill);

        // Redirect to the list bill page
        return redirect($this->getResource()::getUrl('index'));
    }
}
