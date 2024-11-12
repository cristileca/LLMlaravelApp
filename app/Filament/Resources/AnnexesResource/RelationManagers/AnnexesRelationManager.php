<?php

namespace App\Filament\Resources\AnnexesResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use App\Services\DateService;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use App\Models\Annex;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class AnnexesRelationManager extends RelationManager
{
    protected static string $relationship = 'annexes';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $title = 'Anexe';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('files')
                    ->label('Files')
                    ->disk(config('filesystems.default'))
                    ->directory('annexes')
                    ->openable()
                    ->hiddenOn('edit')
                    ->visibleOn('create'),
                Select::make('contract_id')
                    ->label('Contract Number')
                    ->relationship('contract', 'contract_number', function ($query) {
                        return $query->whereNotNull('contract_number');
                    })
                    ->required()
                    ->hiddenOn('edit')
                    ->visibleOn('create'),
                DatePicker::make('annex_date')
                    ->label('Data')
                    ->hiddenOn('create')
                    ->visibleOn('edit'),
                Select::make('status')
                    ->label('Status')
                    ->options(Annex::$statuses)
                    ->required()
                    ->hiddenOn('create')
                    ->visibleOn('edit'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nume')
                    ->searchable(['name', 'changes'])
                    ->sortable()
                    ->badge()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('contract.contract_number')
                    ->label('Numar Contract')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('contract.supplier.name')
                    ->label('Furnizor')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make("annex_date")
                    ->label('Data')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state) => self::formatDate($state))
                    ->badge()
                    ->color('info')
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Progres')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        // Use the human-readable status text from the $statuses array
                        return Annex::$statuses[$state] ?? $state;
                    })
                    ->color(function ($state) {
                        // Define colors based on the status
                        switch ($state) {
                            case Annex::STATUS_SUCCESS:
                                // Green color for success
                                return 'success';
                            case Annex::STATUS_FAILED:
                                // Red color for failure/error
                                return 'danger';
                            case Annex::STATUS_IN_PROGRESS:
                            case Annex::STATUS_OCR_IN_PROGRESS:
                            case Annex::STATUS_LLM_IN_PROGRESS:
                                // Orange color for in-progress
                                return 'warning';
                            default:
                                // Default gray color for others
                                return 'secondary';
                        }
                    })
            ])
            ->filters([
                DateRangeFilter::make('annex_date')
                    ->label('Data Anexei')
                    ->separator(' - ')
                    ->linkedCalendars(false)
                    ->disableRanges()
                    ->placeholder("Introdu Data Anexei")
                    ->showDropdowns()->minYear(2010)->maxYear(2030)
                    ->startDate(null)
                    ->endDate(null),
                Tables\Filters\Filter::make('suppliers_on_annexes')
                    ->form([
                        Select::make('suppliers_on_annexes')
                            ->label('Furnizori')
                            ->multiple()
                            ->options(function () {
                                return \App\Models\Supplier::whereHas('contracts.annexes')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query->when(
                            !empty($data['suppliers_on_annexes']),
                            fn($query) => $query->whereHas(
                                'contract.annexes',
                                fn($query) =>
                                $query->whereIn('supplier_id', $data['suppliers_on_annexes'])
                            )
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['suppliers_on_annexes'])) {
                            return null;
                        }

                        // Fetch supplier names for indication
                        $supplierNames = \App\Models\Supplier::whereIn('id', $data['suppliers_on_annexes'])
                            ->pluck('name')
                            ->join(', ');

                        return 'Furnizori pe Anexe: ' . $supplierNames;
                    }),
            ])
            ->defaultSort('annex_date', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->successNotification(null)
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn($record) => route('filament.admin.resources.annexes.view', ['record' => $record]));
    }

    protected static function formatDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        $dateService = app(DateService::class);
        $parsedDate = $dateService->parseDateWithMultipleFormats($date);

        return $parsedDate ? $parsedDate->format('d/m/Y') : $date;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Automatically set "contract_id"
        $data['contract_id'] = $this->ownerRecord->id;
        return $data;
    }

    public static function getModelLabel(): string
    {
        return __('anexă');
    }

    public static function getPluralModelLabel(): string
    {
        return __('anexe');
    }
}
