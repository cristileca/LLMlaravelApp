<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlowResource\Pages;
use App\Filament\Resources\FlowResource\RelationManagers;
use App\Models\Flow;
use App\Models\StageFlow;
use Auth;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Filters\SelectFilter;
use Tapp\FilamentValueRangeFilter\Filters\ValueRangeFilter;

class FlowResource extends Resource
{
    protected static ?string $model = Flow::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Denumire flux aprobare'),
                Select::make('cost_center')
                    ->options(function () {
                        return \App\Models\CostCenter::pluck('name', 'id')->toArray();
                    })
                    ->multiple()
                    ->label('Centru de cost'),
                Section::make('Flux')
                    ->schema([
                        TextInput::make('min_price')
                            ->numeric()
                            ->label('Pretul minim al facturilor')
                            ->extraAttributes([
                                "style" => 'width: 100px'
                            ]),
                        TextInput::make('max_price')
                            ->numeric()
                            ->label('Pretul maxim al facturilor')
                            ->extraAttributes([
                                "style" => 'width: 100px'
                            ]),
                    ])
                    ->columns(2),
                Repeater::make('stages')
                    ->relationship()
                    ->label('Responsabili')
                    ->itemLabel(function () {
                        static $position = 1;
                        return $position++;
                    })
                    ->schema([
                        Select::make('principal_person')
                            ->label('Persoana principala')
                            ->options(function () {
                                return \App\Models\User::pluck('name', 'id')->toArray();
                            })
                            ->afterStateUpdated(function (Set $set,  $state) {
                                return $set('principal_person_email', User::where('id', $state)->first()->email);
                            }),
                        Hidden::make('principal_person_email')
                            ->default('-'),
                        Checkbox::make('is_person')
                            ->label('Adauga persoana secundara')
                            ->live(),
                        Select::make('second_person_name')
                            ->label('Persoana secundara')
                            ->options(function () {
                                return \App\Models\User::pluck('name', 'id')->toArray();
                            })
                            ->afterStateUpdated(function (Set $set,  $state) {
                                return $set('second_person_name', User::where('id', $state)->first()->email);
                            })
                            ->hidden(fn(Get $get): bool => !$get('is_person')),
                        Hidden::make('second_person_email'),
                        TextInput::make('maximum_step_time')
                            ->numeric()
                            ->label('Termen maxim durata pas'),
                        Hidden::make('stage_number')
                            ->default(1)
                            ->label('Numar Etapa'),
                        Hidden::make('status_stage')
                            ->label('status din stage ')
                            ->default('-')
                            ->required()
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label("Denumire Flux")
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('stages')
                    ->label('Responsabili')
                    ->default('N/A')
                    ->formatStateUsing(function ($state, $record) {
                        $stages = $record->stages;
                        if ($stages->isEmpty()) {
                            return 'N/A';
                        }
                        $responsibles = $stages->map(function ($stage) {
                            $principalName = User::where('id', $stage->principal_person)->pluck('name')->first();
                            $secondaryName = User::where('id', $stage->second_person_name)->pluck('name')->first();
                            $style = "style='
                                display: inline-block;
                                background-color: #e5e7eb; /* Gri deschis */
                                color: #374151; /* Gri mai închis */
                                padding: 4px 8px;
                                border-radius: 9999px;
                                font-size: 12px;
                                font-weight: 500;
                                margin-right: 4px;
                            '";
                            $principalBadge = $principalName ? "<span {$style} >{$principalName}</span>" : 'N/A';
                            $secondaryBadge = $principalName ? "<span {$style} >{$secondaryName}</span>" : 'N/A';

                            return $principalBadge . " " . ($secondaryBadge ?? 'N/A');
                        });
                        return $responsibles->join('<br> <br>');
                    })
                    ->html(),
                Tables\Columns\TextColumn::make('notification_time')
                    ->sortable()
                    ->default('N/A')
                    ->label("Termen Notificare"),
                Tables\Columns\TextColumn::make('maximum_step_time')
                    ->sortable()
                    ->default('N/A')
                    ->label("Termen Maxim Duarata Pas")
            ])
            ->filters([
                SelectFilter::make('suppliers')
                    ->label("Furnizori")
                    ->relationship('suppliers', 'name')
                    ->preload()
                    ->multiple()
                    ->query(function (Builder $query, array $data) {
                        if (!isset($data['values']) || empty($data['values'])) {
                            return; // Early return if 'values' is not set or is empty
                        }
                        foreach ($data['values'] as $supplierId) {
                            $query->whereHas('suppliers', function (Builder $query) use ($supplierId) {
                                $query->where('suppliers.id', $supplierId);
                            });
                        }
                    }),
                SelectFilter::make('responsible_user')
                    ->label('Responsabili')
                    ->multiple()
                    ->options(function () {
                        return \App\Models\User::pluck('name', 'id')->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!isset($data['values']) || empty($data['values'])) {
                            return; // Early return if 'values' is not set or is empty
                        }
                        foreach ($data['values'] as $user) {
                            $query->whereHas('stages', function (Builder $query) use ($user) {
                                $query->where('principal_person', $user)
                                    ->orWhere('second_person_name', $user);
                            });
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlows::route('/'),
            'create' => Pages\CreateFlow::route('/create'),
            'view' => Pages\ViewFlow::route('/{record}'),
            'edit' => Pages\EditFlow::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Flux');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Fluxuri');
    }
}
