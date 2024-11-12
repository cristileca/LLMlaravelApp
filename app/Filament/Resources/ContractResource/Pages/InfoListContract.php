<?php

namespace App\Filament\Resources\ContractResource\Pages;

use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\ViewEntry;
use App\Services\LLMOCRService;

class InfoListContract
{
    public static function schema(Infolist $infolist): Infolist
    {
        $contract = $infolist->getRecord();

        // Decode the summary and format the changes for each annex
        $annexes = $contract->annexes;
        foreach ($annexes as $annex) {
            $annex->changes = formatSummary(json_decode($annex->summary));
        }

        return $infolist
            ->schema([
                ViewEntry::make('contract.details')
                    ->view('filament.pages.contracts.show', [
                        'presigned' => (new LLMOCRService())->getFileFromGCS(implode('', $contract->files)),
                        'supplier_name' => $contract->supplier->name ?? "N/A",
                        'supplier_id' => $contract->supplier->id ?? "N/A",
                        'supplier_address' => $contract->supplier->address ?? "N/A",
                        'supplier_trade_register_number' => $contract->supplier->trade_register_number ?? "N/A",
                        'client' => $contract->contractDetail->client ?? "N/A",
                        'objective' => $contract->contractDetail->objective ?? "N/A",
                        'delivery_conditions' => $contract->contractDetail->delivery_conditions ?? "N/A",
                        'price' => $contract->contractDetail->price ?? "N/A",
                        'penalties' => $contract->contractDetail->penalties ?? "N/A",
                        'payment_conditions' => $contract->contractDetail->payment_conditions ?? "N/A",
                        'contract_term' => $contract->contractDetail->contract_term ?? "N/A",
                        'issue_date' => formatDate($contract->issue_date) ?? "N/A",
                        'contract_number' => $contract->contract_number ?? "N/A",
                        'starting_date' => formatDate($contract->starting_date) ?? "N/A",
                        'summary' => $contract->summary ?? "N/A",
                        'raw_text' => $contract->raw_text,
                    ])
                    ->columnSpanFull(),
                Section::make('LOG - Modificari')
                    ->schema([
                        ViewEntry::make('Annexes viewer')
                            ->view(
                                'filament.pages.annexes.index',
                                ['summary' => $annexes]
                            )
                            ->columnSpanFull(),
                    ])
            ]);
    }
}
