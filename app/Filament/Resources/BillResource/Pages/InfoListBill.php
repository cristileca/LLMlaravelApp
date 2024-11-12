<?php

namespace App\Filament\Resources\BillResource\Pages;

use Filament\Infolists\Infolist;
use Filament\Infolists\Components\ViewEntry;
use App\Models\Bill;
use App\Models\Contract;
use Spatie\Activitylog\Models\Activity;
use App\Services\LLMOCRService;
use Illuminate\Support\Facades\Auth;

class InfoListBill
{
    public static function schema(Infolist $infolist): Infolist
    {
        $isSuperAdmin = Auth::user()->hasRole('super_admin');

        // Fetch the analysis data and prepare status color
        $analysisData = json_decode($infolist->getRecord()->analysis, true) ?? null;
        $finalStatus = $infolist->getRecord()->acceptance_status;
        $statusName = self::getStatusName($finalStatus);
        $statusColor = self::getStatusColor($finalStatus);

        return $infolist
            ->schema(array_filter([
                $isSuperAdmin ? ViewEntry::make('activity_logs')
                    ->view('filament.pages.bills.activity-logs.index', [
                        'activities' => Activity::query()
                            ->where('subject_id', $infolist->getRecord()->id)
                            ->orderBy('created_at', 'desc')
                            ->get()
                            ->map(function ($activity) {
                                if ($activity->log_name === "bill") {
                                    $description = $activity->event === 'created' ? '' : self::formatActivityChanges($activity);
                                } else {
                                    $description = $activity->description;
                                }
                                return [
                                    'activity' => ucfirst($activity->description),
                                    'description' => $description,
                                    'created_at' => \Carbon\Carbon::parse($activity->created_at)->format('d/m/Y H:i:s'),
                                ];
                            })
                            ->toArray(),
                    ])
                    ->columnSpanFull() : null,
                ViewEntry::make('bill.details')
                    ->view('filament.pages.bills.show', [
                        'supplier_name' => $infolist->getRecord()->contract->supplier->name ?? null,
                        'supplier_id' => $infolist->getRecord()->contract->supplier->id ?? null,
                        'supplier_address' => $infolist->getRecord()->contract->supplier->address ?? null,
                        'trade_register_number' => $infolist->getRecord()->contract->supplier->trade_register_number ?? null,
                        // Contract information
                        'contract_number' => $infolist->getRecord()->contract->contract_number ?? null,
                        'contract_id' => $infolist->getRecord()->contract->id ?? null,
                        'contract_date' => $infolist->getRecord()->contract->issue_date ?? null,
                        // Bill information
                        'presigned' => (new LLMOCRService())->getFileFromGCS(implode('', $infolist->getRecord()->files)),
                        'number' => $infolist->getRecord()->number,
                        'date' => formatDate($infolist->getRecord()->date),
                        'due_date' => formatDate($infolist->getRecord()->due_date),
                        'fee' => $infolist->getRecord()->fee,
                        'analysis' => $infolist->getRecord()->analysis,
                        'last_status_message' => $infolist->getRecord()->last_status_message,
                        'acceptance_status' => [
                            'status' => Bill::$acceptanceStatuses[$infolist->getRecord()->acceptance_status] ?? $infolist->getRecord()->acceptance_status,
                            'color' => match ($infolist->getRecord()->acceptance_status) {
                                'approved' => 'green',
                                'unapproved_check' => 'yellow',
                                'unapproved_alert' => 'red',
                                'check_if_approved' => 'gray',
                                default => 'black',
                            },
                        ],
                    ])
                    ->columnSpanFull(),

                // Associate bill with a cost center
                ViewEntry::make('cost_center')
                    ->view('filament.pages.bills.cost-center.index', [
                        'bill' => $infolist->getRecord(),
                    ])
                    ->columnSpanFull(),

                // Analysis section
                ViewEntry::make('analysis')
                    ->view('filament.pages.bills.analysis.index', [
                        'data' => $analysisData,
                        'statusColor' => $statusColor,
                        'statusName' => $statusName,
                    ])
                    ->columnSpanFull(),

                // Comments section
                ViewEntry::make('comments')
                    ->view('filament.pages.bills.comments.index', [
                        'bill' => $infolist->getRecord(),
                    ])
                    ->columnSpanFull(),
            ]));
    }

    public static function getStatusName(?string $status): string
    {
        $statusMapping = [
            Bill::STATUS_APPROVED => 'Aprobat',
            Bill::STATUS_UNAPPROVED_ALERT  => 'Refuzat',
        ];

        return $statusMapping[$status] ?? '' ?? ucfirst($status);
    }

    public static function getStatusColor(?string $status): string
    {
        $statusColors = [
            Bill::STATUS_APPROVED => '#28a745',
            Bill::STATUS_UNAPPROVED_ALERT => '#dc3545',
        ];

        return $statusColors[$status] ?? '#ffffff';
    }

    private static function formatActivityChanges($activity): string
    {
        if (!$activity?->properties) {
            return '';
        }

        $changes = [];

        $convertToString = function ($value) {
            if (is_array($value)) {
                return implode(', ', $value);
            }
            return $value;
        };

        $logChange = function (string $field, $oldValue, $newValue) use (&$changes, $convertToString) {
            // Get the translated field name from the Bill.php language file
            $fieldName = __('Bill.' . $field);

            switch ($field) {
                case 'contract_id':
                    $oldContractNumber = self::getContractNumber($oldValue);
                    $newContractNumber = self::getContractNumber($newValue);

                    $changes[] = sprintf(
                        "Factura a fost modificată: Câmpul 'Număr de contract' a fost schimbat de la '%s' la '%s'.",
                        $convertToString($oldContractNumber) ?: 'necompletat',
                        $convertToString($newContractNumber) ?: 'necompletat'
                    );
                    return;

                case 'analysis':
                    // Custom logic for analysis field
                    if ($oldValue === null && $newValue !== null) {
                        $changes[] = "Analiza facturii a fost creată\n";
                    } elseif ($oldValue !== null && $newValue !== null) {
                        $changes[] = "Analiza facturii a fost actualizată\n";
                    }
                    return;

                case 'text':
                    // Custom logic for text (comments)
                    if ($oldValue !== null && $newValue !== null) {
                        $userName = Auth::user()->name;
                        $changes[] = sprintf(
                            "%s a modificat câmpul 'Comentariu' de la '%s' la '%s'.",
                            $userName,
                            $convertToString($oldValue) ?: 'necompletat',
                            $convertToString($newValue) ?: 'necompletat'
                        );
                    }
                    return;

                default:
                    $changes[] = sprintf(
                        "Factura a fost modificată: Câmpul '%s' a fost schimbat(ă) de la '%s' la '%s'.",
                        $fieldName,
                        $convertToString($oldValue) ?? 'necompletat',
                        $convertToString($newValue) ?? 'necompletat'
                    );
                    return;
            }
        };

        $properties = json_decode($activity->properties, true);
        $oldAttributes = $properties['old'] ?? [];
        $newAttributes = $properties['attributes'] ?? [];

        foreach ($oldAttributes as $field => $oldValue) {
            $newValue = $newAttributes[$field] ?? null;

            if ($oldValue !== $newValue) {
                $logChange($field, $oldValue, $newValue);
            }
        }

        foreach ($newAttributes as $field => $newValue) {
            if (!array_key_exists($field, $oldAttributes)) {
                $logChange($field, null, $newValue);
            }
        }

        return !empty($changes) ? implode("\n", $changes) : '';
    }

    private static function getContractNumber($contractId): ?string
    {
        return $contractId ? Contract::find($contractId)?->contract_number : null;
    }
}
