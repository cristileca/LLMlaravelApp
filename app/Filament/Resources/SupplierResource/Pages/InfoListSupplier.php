<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use Filament\Infolists\Infolist;
use Filament\Infolists\Components\ViewEntry;

class InfoListSupplier
{
    public static function schema(Infolist $infolist): Infolist
    {
        $contracts = $infolist->getRecord()->contracts;
        $bills = [];

        foreach ($contracts as $contract) {
            foreach ($contract->bills as $bill) {
                $bills[] = $bill;
            }
        }
        return $infolist
            ->schema([
                ViewEntry::make('supplier.details')
                    ->view('filament.pages.suppliers.show', [
                        'supplier' => $infolist->getRecord(),
                        'bills' => $bills
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
