<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return parent::table($table)->modifyQueryUsing(fn ($query) => $query->whereNull('lotification_id')->withCount([
            'lots as available_count'=>fn(\Illuminate\Database\Eloquent\Builder $query)=>$query->where('availability_status','disponible'),
            'lots as reserved_count'=>fn(\Illuminate\Database\Eloquent\Builder $query)=>$query->where('availability_status','apartado'),
            'lots as sold_count'=>fn(\Illuminate\Database\Eloquent\Builder $query)=>$query->where('availability_status','vendido'),
        ]));
    }

    public function getTabs(): array
    {
        return [
            'lotifications'=>\Filament\Schemas\Components\Tabs\Tab::make('Lotificaciones')->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query)=>$query->where('is_lotification',true)),
            'independent'=>\Filament\Schemas\Components\Tabs\Tab::make('Terrenos independientes')->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query)=>$query->where('is_lotification',false)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
