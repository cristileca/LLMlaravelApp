<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillResource\RelationManagers\BillsRelationManager;
use App\Filament\Resources\AnnexesResource\RelationManagers\AnnexesRelationManager;
use Filament\Tables;
use App\Models\Contract;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ContractResource\Pages;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use App\Filament\Resources\ContractResource\Pages\InfoListContract;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Filament\Notifications\Notification;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('files')
                    ->multiple()
                    ->label('Documente')
                    ->required()
                    ->directory('contracts')
                    ->disk(config('filesystems.default'))
                    ->previewable()
                    ->openable()
                    ->hiddenOn('edit')
                    ->visibleOn('create'),
                DatePicker::make('issue_date')
                    ->label('Data Semnarii')
                    ->hiddenOn('create')
                    ->visibleOn('edit'),
                DatePicker::make('starting_date')
                    ->label('Data Initierii')
                    ->hiddenOn('create')
                    ->visibleOn('edit'),
                TextInput::make('contract_number')
                    ->label('Numarul Contractului')
                    ->hiddenOn('create')
                    ->visibleOn('edit'),
                Select::make('supplier_id')
                    ->label('Furnizor')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->hiddenOn('create')
                    ->visibleOn('edit'),
                Select::make('status')
                    ->label('Status')
                    ->required()
                    ->options(Contract::$statuses)
                    ->hiddenOn('create')
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("contract_number")
                    ->label("Numar Contract")
                    ->searchable(['contract_number', 'summary'])
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Furnizor')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make("issue_date")
                    ->label("Data Semnarii")
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn($state) => formatDate($state))
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Progres')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        // Use the human-readable status text from the $statuses array
                        return Contract::$statuses[$state] ?? $state;
                    })
                    ->color(function ($state) {
                        // Define colors based on the status
                        switch ($state) {
                            case Contract::STATUS_SUCCESS:
                                // Green color for success
                                return 'success';
                            case Contract::STATUS_FAILED:
                                // Red color for failure/error
                                return 'danger';
                            case Contract::STATUS_IN_PROGRESS:
                            case Contract::STATUS_OCR_IN_PROGRESS:
                            case Contract::STATUS_LLM_IN_PROGRESS:
                                // Orange color for in-progress
                                return 'warning';
                            default:
                                // Default gray color for others
                                return 'secondary';
                        }
                    })
            ])
            ->filters([
                DateRangeFilter::make('issue_date')
                    ->label('Data Semnarii')
                    ->separator(' - ')
                    ->linkedCalendars(false)
                    ->disableRanges()
                    ->placeholder("Introdu Data Semnarii Contractului")
                    ->showDropdowns()->minYear(2010)->maxYear(2030)
                    ->startDate(null)
                    ->endDate(null),
                Tables\Filters\Filter::make('suppliers_on_contracts')
                    ->form([
                        Select::make('suppliers_on_contracts')
                            ->label('Furnizori pe Contracte')
                            ->multiple()
                            ->options(function () {
                                return \App\Models\Supplier::whereHas('contracts')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query->when(
                            !empty($data['suppliers_on_contracts']),
                            fn($query) => $query->whereHas(
                                'supplier',
                                fn($query) =>
                                $query->whereIn('id', $data['suppliers_on_contracts'])
                            )
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['suppliers_on_contracts'])) {
                            return null;
                        }

                        // Fetch supplier names for indication
                        $supplierNames = \App\Models\Supplier::whereIn('id', $data['suppliers_on_contracts'])
                            ->pluck('name')
                            ->join(', ');

                        return 'Furnizori pe Contracte: ' . $supplierNames;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
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
                                        throw $e;
                                    }
                                }
                            }
                        }),
                ]),
            ])
            ->recordUrl(fn($record) => route('filament.admin.resources.contracts.view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [
            AnnexesRelationManager::class,
            BillsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
            'view' => Pages\ViewContract::route('/{record}')
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return InfoListContract::schema($infolist);
    }

    public static function getModelLabel(): string
    {
        return __('Contract');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Contracte');
    }
}
