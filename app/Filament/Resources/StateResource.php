<?php

namespace App\Filament\Resources;

use App\Models\State;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StateResource extends Resource
{
    protected static ?string $model = State::class;

    protected static ?string $modelLabel = 'estado';

    protected static ?string $pluralModelLabel = 'Estados';

    protected static ?string $slug = 'estados';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            TextInput::make('name')->label('Nombre')->required()->maxLength(255)->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->label('Nombre')->searchable()->sortable()])
            ->recordActions([EditAction::make(), DeleteAction::make()->tooltip('Solo se puede eliminar si no tiene registros dependientes.')]);
    }

    public static function getPages(): array
    {
        return ['index' => StateResource\Pages\ManageState::route('/')];
    }
}
