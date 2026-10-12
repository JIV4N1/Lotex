<?php

namespace App\Filament\Resources\StateResource\Pages;

use App\Filament\Resources\StateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageState extends ManageRecords
{
    protected static string $resource = StateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
