<?php

namespace App\Filament\Resources;

use App\Models\Municipality;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class MunicipalityResource extends Resource
{
    protected static ?string $model = Municipality::class;

    protected static ?string $modelLabel = 'municipio';

    protected static ?string $pluralModelLabel = 'Municipios';

    protected static ?string $slug = 'municipios';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('state_id')->label('Estado')->relationship('state', 'name')->searchable()->preload()->required(),
            TextInput::make('name')->label('Nombre')->required()->maxLength(255)->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('state_id', $get('state_id'))),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->label('Nombre')->searchable()->sortable(), TextColumn::make('state.name')->label('Estado')])
            ->recordActions([EditAction::make(), DeleteAction::make()->tooltip('Solo se puede eliminar si no tiene registros dependientes.')]);
    }

    public static function getPages(): array
    {
        return ['index' => MunicipalityResource\Pages\ManageMunicipality::route('/')];
    }
}
