<?php

namespace App\Filament\Resources;

use App\Models\Zone;
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

class ZoneResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Ubicaciones';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 5;

    protected static ?string $model = Zone::class;

    protected static ?string $modelLabel = 'zona';

    protected static ?string $pluralModelLabel = 'Zonas';

    protected static ?string $slug = 'zonas';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('municipality_id')->label('Municipio / Estado')->relationship('municipality', 'name')->getOptionLabelFromRecordUsing(fn ($record) => $record->name.' / '.$record->state->name)->searchable()->preload()->required(),
            TextInput::make('name')->label('Nombre')->required()->maxLength(255)->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('municipality_id', $get('municipality_id'))),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->label('Nombre')->searchable()->sortable(), TextColumn::make('municipality.name')->label('Municipio'), TextColumn::make('municipality.state.name')->label('Estado')])
            ->recordActions([EditAction::make(), DeleteAction::make()->tooltip('Solo se puede eliminar si no tiene registros dependientes.')]);
    }

    public static function getPages(): array
    {
        return ['index' => ZoneResource\Pages\ManageZone::route('/')];
    }
}
