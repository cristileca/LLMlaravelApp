<?php

namespace App\Filament\Pages;

use App\Models\Flow;
use App\Models\StageFlow;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Tasks extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-numbered-list';

    protected static string $view = 'filament.pages.tasks';

    protected static ?string $title = 'Sarcini';

}

