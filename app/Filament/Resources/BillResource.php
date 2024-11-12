<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillResource\Pages;
use App\Models\Bill;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use App\Filament\Resources\BillResource\Pages\InfoListBill;
use Filament\Infolists\Infolist;
use Carbon\Carbon;
use Filament\Tables\Filters\Indicator;
use Illuminate\Support\HtmlString;

class BillResource extends Resource
{
    protected static ?string $model = Bill::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    // Invoice

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Grid::make()
                    ->schema([
                        // First row
                        \Filament\Forms\Components\Grid::make()->columns(2)->schema([
                            FileUpload::make('files')
                                ->multiple()
                                ->label('Documente')
                                ->required()
                                ->directory('bills')
                                ->disk(config('filesystems.default'))
                                ->openable()
                                ->columnSpan(1)
                                ->hiddenOn('edit')
                                ->visibleOn('create'),
                        ]),

                        // Second row
                        \Filament\Forms\Components\Grid::make()->columns(2)->schema([
                            Select::make('contract_id')
                                ->label('Numarul contractului')
                                ->relationship('contract', 'contract_number', function ($query) {
                                    return $query->whereNotNull('contract_number');
                                })
                                ->searchable()
                                ->preload()
                                ->columnSpan(1)
                                ->hiddenOn('edit')
                                ->visibleOn('create'),
                        ]),
                    ]),
                // edit section
                TextInput::make('number')
                    ->label('Numarul facturii')
                    ->hiddenOn('create')
                    ->visibleOn('edit'),
                TextInput::make('fee')
                    ->label('Costul facturii')
                    ->hiddenOn('create')
                    ->visibleOn('edit'),
                DatePicker::make('date')
                    ->label('Data facturii')
                    ->hiddenOn('create')
                    ->visibleOn('edit'),
                DatePicker::make('due_date')
                    ->label('Data Scadenta Facturii')
                    ->hiddenOn('create')
                    ->visibleOn('edit'),
                Select::make('status')
                    ->label('Status')
                    ->options(Bill::$statuses)
                    ->required()
                    ->hiddenOn('create')
                    ->visibleOn('edit'),
                Select::make('acceptance_status')
                    ->label('Status acceptanta')
                    ->options(Bill::$acceptanceStatuses)
                    ->required()
                    ->default('check_if_approved')
                    ->hiddenOn('create')
                    ->visibleOn('edit'),
                Select::make('contract_id')
                    ->label('Numarul contractului')
                    ->relationship('contract', 'contract_number', function ($query) {
                        return $query->whereNotNull('contract_number');
                    })
                    ->searchable()
                    ->preload()
                    ->hiddenOn('create')
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Numar Factura')
                    // implemented the feature to search by any data after Bill OCR
                    ->searchable(['number', 'raw_text'])
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('contract.supplier.name')
                    ->label('Furnizor')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('contract.contract_number')
                    ->label('Numar Contract')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('fee')
                    ->label('Cost')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn($state) => is_numeric($state) ? number_format((float) $state, 2) . ' RON' : 'N/A')
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Data')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn($state) => formatDate($state))
                    ->default('N/A'),
                // due date logic
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Data Scadenta')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn($state) => formatDate($state))
                    ->color(function ($state) {
                        if ($state && strtotime($state)) {
                            $dueDate = Carbon::parse($state);
                            if ($dueDate->isToday()) {
                                // Color for today
                                return 'warning';
                            }
                            if ($dueDate->isFuture()) {
                                // Green color if due date is in the future
                                return 'success';
                            }
                        }
                        // Red color if due date has passed or no date is specified
                        return 'danger';
                    })
                    ->icon(function ($state) {
                        if ($state && strtotime($state)) {
                            $dueDate = Carbon::parse($state);
                            if ($dueDate->isToday()) {
                                // Clock icon for today
                                return 'heroicon-o-clock';
                            }
                            if ($dueDate->isFuture()) {
                                // Check icon for future dates
                                return 'heroicon-o-check';
                            }
                        }
                        // X-mark icon for past/not specified dates
                        return 'heroicon-o-x-mark';
                    })
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        // Use the human-readable status text from the $statuses array
                        return Bill::$statuses[$state] ?? $state;
                    })
                    ->color(function ($state) {
                        // Define colors based on the status
                        switch ($state) {
                            case Bill::STATUS_SUCCESS:
                                // Green color for success
                                return 'success';
                            case Bill::STATUS_FAILED:
                                // Red color for failure/error
                                return 'danger';
                            case Bill::STATUS_IN_PROGRESS:
                            case Bill::STATUS_OCR_IN_PROGRESS:
                            case Bill::STATUS_LLM_IN_PROGRESS:
                                // Orange color for in-progress
                                return 'warning';
                            default:
                                // Default gray color for others
                                return 'secondary';
                        }
                    })
            ])
            ->filters([
                DateRangeFilter::make('date')
                    ->label('Data')
                    ->separator(' - ')
                    ->linkedCalendars(false)
                    ->disableRanges()
                    ->placeholder("Introdu Data Facturii")
                    ->showDropdowns()->minYear(2010)->maxYear(2030)
                    ->startDate(null)
                    ->endDate(null),
                DateRangeFilter::make('due_date')
                    ->label('Data Scadenta')
                    ->separator(' - ')
                    ->linkedCalendars(false)
                    ->disableRanges()
                    ->placeholder("Introdu Data Scadentei Facturii")
                    ->showDropdowns()->minYear(2010)->maxYear(2030)
                    ->startDate(null)
                    ->endDate(null),
                Tables\Filters\Filter::make('suppliers')
                    ->form([
                        Select::make('suppliers')
                            ->label('Furnizori')
                            ->multiple()
                            ->options(function () {
                                return \App\Models\Supplier::whereHas('contracts.bills')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query->when(
                            !empty($data['suppliers']),
                            fn($query) => $query->whereHas(
                                'contract.supplier',
                                fn($query) =>
                                $query->whereIn('id', $data['suppliers'])
                            )
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['suppliers'])) {
                            return null;
                        }

                        // Fetch the names of the selected suppliers
                        $supplierNames = \App\Models\Supplier::whereIn('id', $data['suppliers'])
                            ->pluck('name')
                            ->join(', ');

                        return 'Furnizori: ' . $supplierNames;
                    }),
                Tables\Filters\Filter::make('status')
                    ->form([
                        Select::make('status')
                            ->label('Status')
                            ->multiple()
                            ->options([
                                Bill::STATUS_OCR_IN_PROGRESS => Bill::$statuses[Bill::STATUS_OCR_IN_PROGRESS],
                                Bill::STATUS_LLM_IN_PROGRESS => Bill::$statuses[Bill::STATUS_LLM_IN_PROGRESS],
                                Bill::STATUS_FAILED => Bill::$statuses[Bill::STATUS_FAILED],
                                Bill::STATUS_IN_PROGRESS => Bill::$statuses[Bill::STATUS_IN_PROGRESS],
                                Bill::STATUS_SUCCESS => Bill::$statuses[Bill::STATUS_SUCCESS],
                            ])
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query->when(
                            !empty($data['status']),
                            fn($query) => $query->whereIn('status', $data['status'])
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['status'])) {
                            return null;
                        }

                        $selectedStatuses = collect($data['status'])
                            ->map(fn($status) => Bill::$statuses[$status] ?? $status)
                            ->join(', ');

                        return 'Status: ' . $selectedStatuses;
                    }),
                Tables\Filters\Filter::make('fee_range')
                    ->form([
                        \Filament\Forms\Components\Grid::make('fee_range')
                            ->columns(2)
                            ->schema([
                                TextInput::make('cost_from')
                                    ->label('Interval Cost')
                                    ->placeholder('ex: 500')
                                    ->numeric()
                                    ->columnSpan(1),
                                TextInput::make('cost_to')
                                    ->label(new HtmlString('<br />'))
                                    ->placeholder('ex: 8000')
                                    ->numeric()
                                    ->columnSpan(1)
                            ])
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query->when(
                            isset($data['cost_from']) && is_numeric($data['cost_from']),
                            fn($query) => $query->where('fee', '>=', (float) $data['cost_from'])
                        );

                        $query->when(
                            isset($data['cost_to']) && is_numeric($data['cost_to']),
                            fn($query) => $query->where('fee', '<=', (float) $data['cost_to'])
                        );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        $minFee = isset($data['cost_from']) && is_numeric($data['cost_from']) ? (float) $data['cost_from'] : null;
                        $maxFee = isset($data['cost_to']) && is_numeric($data['cost_to']) ? (float) $data['cost_to'] : null;

                        if ($minFee !== null || $maxFee !== null) {
                            $label = $minFee !== null && $maxFee !== null
                                ? "Interval Cost: $minFee - $maxFee RON"
                                : ($minFee !== null
                                    ? "Cost minim: $minFee RON"
                                    : "Cost maxim: $maxFee RON");

                            $indicators[] = Indicator::make($label)->removeField('fee_range');
                        }

                        return $indicators;
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn($record) => route('filament.admin.resources.bills.view', ['record' => $record]));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return InfoListBill::schema($infolist);
    }

    public static function getRelations(): array
    {
        return [
            // Define any relations if needed
        ];
    }

    protected static function loadContractSupplier(Bill $record): ?string
    {
        $record->load('contract.supplier');
        return $record->contract->supplier->name ?? null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBills::route('/'),
            'create' => Pages\CreateBill::route('/create'),
            'edit' => Pages\EditBill::route('/{record}/edit'),
            'view' => Pages\ViewBill::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Factură');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Facturi');
    }
}
