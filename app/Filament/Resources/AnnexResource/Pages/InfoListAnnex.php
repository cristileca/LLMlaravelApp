<?php

namespace App\Filament\Resources\AnnexResource\Pages;

use Filament\Infolists\Infolist;
use Filament\Infolists\Components\ViewEntry;
use App\Services\LLMOCRService;

class InfoListAnnex
{
    public static function schema(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                ViewEntry::make('annex.details')
                    ->view('filament.pages.annexes.show', [
                        'supplier_name' => $infolist->getRecord()->contract->supplier->name ?? null,
                        'supplier_id' => $infolist->getRecord()->contract->supplier->id ?? null,
                        'supplier_address' => $infolist->getRecord()->contract->supplier->address ?? null,
                        'trade_register_number' => $infolist->getRecord()->contract->supplier->trade_register_number ?? null,
                        'annex' => $infolist->getRecord(),
                        'summary' => formatSummary(json_decode($infolist->getRecord()->summary)),
                        'contract_number' => $infolist->getRecord()->contract->contract_number,
                        'contract_date' => $infolist->getRecord()->contract->issue_date,
                        'contract_id' => $infolist->getRecord()->contract->id,
                        'presigned' => (new LLMOCRService())->getFileFromGCS($infolist->getRecord()->files),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
