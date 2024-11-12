<?php

namespace App\Filament\Resources;

use Filament\Infolists\Infolist;
use App\Filament\Resources\AnnexResource\Pages;
use App\Filament\Resources\AnnexResource\Pages\InfoListAnnex;
use App\Models\Annex;
use App\Services\DateService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;


class AnnexResource extends Resource
{
    protected static ?string $model = Annex::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('files')
                    ->label('Documente')
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nume')
                    ->searchable(['name', 'summary'])
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
                    ->label('Data')
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
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn($record) => route('filament.admin.resources.annexes.view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return InfoListAnnex::schema($infolist);
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnexes::route('/'),
            'create' => Pages\CreateAnnex::route('/create'),
            'edit' => Pages\EditAnnex::route('/{record}/edit'),
            'view' => Pages\ViewAnnex::route('/{record}')
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Anexă');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Anexe');
    }
}
