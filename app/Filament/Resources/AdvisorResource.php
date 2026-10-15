<?php

namespace App\Filament\Resources;

use App\Filament\Forms\PropertyFileUpload;
use App\Models\Advisor;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AdvisorResource extends Resource
{
    protected static ?string $model = Advisor::class;

    protected static ?string $modelLabel = 'asesor';

    protected static ?string $pluralModelLabel = 'Asesores';

    protected static ?string $slug = 'asesores';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión comercial';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            PropertyFileUpload::make('photo_path')->label('Fotografía del asesor')->disk('local')->directory('advisor-photos')->visibility('private')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->preventFilePathTampering()->helperText('JPG, PNG o WebP, máximo 5 MB. Puedes sustituir o quitar la fotografía.'),
            TextInput::make('name')->label('Nombre completo')->required()->maxLength(120),
            TextInput::make('areas')->label('Zonas de atención')->maxLength(255)->placeholder('Ej. Tehuacán y alrededores'),
            Textarea::make('bio')->label('Presentación')->rows(4)->maxLength(2000)->columnSpanFull(),
            TextInput::make('position')->label('Orden de aparición')->integer()->minValue(0)->maxValue(9999)->default(0)->required(),
            Toggle::make('is_active')->label('Mostrar en el sitio público')->default(false)->helperText('Publica el perfil en Inicio y Terrenos. Este perfil no crea una cuenta ni concede acceso administrativo.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)->columns([
            TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
            TextColumn::make('areas')->label('Zonas')->searchable()->placeholder('Por completar'),
            IconColumn::make('is_active')->label('Publicado')->boolean(),
            TextColumn::make('position')->label('Orden')->sortable(),
        ])->defaultSort('position')->filters([TernaryFilter::make('is_active')->label('Publicado')])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => AdvisorResource\Pages\ManageAdvisors::route('/')];
    }
}
