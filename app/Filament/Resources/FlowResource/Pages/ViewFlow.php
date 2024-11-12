<?php

namespace App\Filament\Resources\FlowResource\Pages;

use App\Filament\Resources\BillResource;
use App\Filament\Resources\FlowResource;
use Filament\Actions;
use Filament\Forms\Components\Actions as ComponentsActions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;


class ViewFlow extends ViewRecord
{
    protected static string $resource = FlowResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->record->name;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Denumire flux aprobare'),
                        TextInput::make('price_range')
                            ->label('Interval Preț')
                            ->formatStateUsing(function ($record) {
                                return "{$record->min_price} - {$record->max_price}";
                            }),
                    ]),
                Section::make('Responsabili')
                    ->schema([
                        ViewField::make('responsabili')
                            ->label('Responsabili')
                            ->view('filament.forms.components.view-flow-responsabili', [
                                'stages' => $this->record->stages
                            ]),
                    ])
            ]);
    }
}
