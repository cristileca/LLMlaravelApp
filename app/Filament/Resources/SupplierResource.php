<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillResource\RelationManagers\BillsRelationManager;
use App\Filament\Resources\SupplierResource\Pages\InfoListSupplier;
use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label("Nume")
                    ->minLength(3)
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('TIN')
                    ->label("CIF")
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address')
                    ->label("Adresa")
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('trade_register_number')
                    ->label("Numar de Inregistrare")
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label("Email")
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('IBAN')
                    ->label("IBAN")
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label("Numar Telefon")
                    ->tel()
                    ->required()
                    ->maxLength(20),
                Forms\Components\Textarea::make('notes')
                    ->label("Notite")
                    ->rows(1),
                Forms\Components\Select::make('services')
                    ->options([
                        'L' => 'Executie lucrari',
                        'S' => 'Prestari servicii',
                        'P' => 'Achizitie materiale/produse',
                    ])
                    ->multiple()
                    ->label('Tip colaborare'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label("Nume")
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('TIN')
                    ->label("CIF")
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label("Adresa")
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('trade_register_number')
                    ->label("Numar de Inregistrare")
                    ->sortable()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->recordUrl(fn($record) => route('filament.admin.resources.suppliers.view', ['record' => $record]));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return InfoListSupplier::schema($infolist);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
            'view' => Pages\ViewSupplier::route('/{record}')
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Furnizor');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Furnizori');
    }
}
