<?php

namespace App\Filament\Resources;

use App\Filament\Forms\PropertyFileUpload as FileUpload;
use App\Filament\Resources\PropertyResource\Pages;
use App\Models\Property;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PropertyResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Gestión comercial';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 1;

    protected static ?string $model = Property::class;

    protected static ?string $modelLabel = 'terreno';

    protected static ?string $pluralModelLabel = 'Terrenos';

    protected static ?string $slug = 'terrenos';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información principal')->columns(2)->schema([
                TextInput::make('title')->label('Título')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('code')->label('Clave única')->required()->maxLength(64)->unique(ignoreRecord: true),
                TextInput::make('slug')->label('Slug de la ficha')->required()->maxLength(255)->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true)->helperText('Ejemplo: terreno-tehuacan-centro. Cambiarlo cambia el enlace público.'),
                Textarea::make('description')->label('Descripción')->required()->rows(4)->maxLength(20000)->columnSpanFull(),
                TextInput::make('price_mxn')->label('Precio MXN')->numeric()->minValue(0)->maxValue(99999999999999.99)->step('0.01')->required(),
                TextInput::make('area_m2')->label('Superficie m²')->numeric()->minValue(0.01)->maxValue(999999999999.99)->step('0.01')->required(),
                TextInput::make('frontage_m')->label('Frente (m)')->numeric()->minValue(0.01)->maxValue(9999999999.99)->step('0.01'),
                TextInput::make('depth_m')->label('Fondo (m)')->numeric()->minValue(0.01)->maxValue(9999999999.99)->step('0.01'),
            ]),
            Section::make('Ubicación')->columns(2)->schema([
                Select::make('zone_id')->label('Zona / Municipio / Estado')
                    ->relationship('zone', 'name', modifyQueryUsing: fn (Builder $query) => $query->with('municipality.state'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name.' / '.$record->municipality->name.' / '.$record->municipality->state->name)
                    ->searchable()->preload()->required()->helperText('Crea las ubicaciones en Estados, Municipios y Zonas.')->columnSpanFull(),
                Textarea::make('reference_address')->label('Dirección de referencia')->maxLength(2000)->columnSpanFull(),
                TextInput::make('latitude')->label('Latitud')->numeric()->minValue(-90)->maxValue(90)->step('0.0000001')->requiredWith('longitude'),
                TextInput::make('longitude')->label('Longitud')->numeric()->minValue(-180)->maxValue(180)->step('0.0000001')->requiredWith('latitude'),
            ]),
            Section::make('Características')->columns(2)->schema([
                TagsInput::make('services')->label('Servicios')->helperText('Escribe cada servicio y pulsa Enter.')->columnSpanFull(),
                Textarea::make('access')->label('Acceso')->maxLength(5000),
                Textarea::make('topography')->label('Topografía')->maxLength(5000),
                Textarea::make('land_use')->label('Uso de suelo')->maxLength(5000),
                Textarea::make('documentary_summary')->label('Resumen documental')->maxLength(5000),
                Textarea::make('payment_terms')->label('Condiciones de pago')->maxLength(5000)->columnSpanFull(),
            ]),
            Section::make('Fotografías')->description('La primera imagen ordenada será la portada. Reordena, edita el texto alternativo o quita fotografías.')->schema([
                Repeater::make('images')->label('Galería')->relationship()->orderColumn('position')->defaultItems(0)->maxItems(30)->schema([
                    FileUpload::make('path')->label('Fotografía')->disk('local')->directory('property-images')->visibility('private')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->preventFilePathTampering()->required(),
                    TextInput::make('alt_text')->label('Descripción de la imagen')->required()->maxLength(255),
                ])->addActionLabel('Añadir fotografía')->collapsible(),
            ]),
            Section::make('Documentos y planos')->description('PDF hasta 10 MB. Privados por defecto: autoriza solo versiones revisadas sin datos personales reservados.')->schema([
                Repeater::make('documents')->label('Documentos')->relationship()->defaultItems(0)->maxItems(20)->schema([
                    TextInput::make('title')->label('Nombre del documento')->required()->maxLength(255),
                    FileUpload::make('path')->label('Archivo PDF')->disk('local')->directory('property-documents')->visibility('private')->acceptedFileTypes(['application/pdf'])->maxSize(10240)->openable()->downloadable()->preventFilePathTampering()->required(),
                    TextInput::make('type')->label('Tipo (ej. plano, escritura)')->maxLength(255),
                    TextInput::make('issuer')->label('Emisor')->maxLength(255),
                    DatePicker::make('issued_at')->label('Fecha de emisión'),
                    Toggle::make('is_public')->label('Autorizar consulta pública de esta versión revisada')->default(false)->helperText('Solo se muestra con el terreno publicado. No certifica validez jurídica.'),
                ])->addActionLabel('Añadir documento')->collapsible(),
            ]),
            Section::make('Publicación')->schema([
                Select::make('publication_status')->label('Publicación')->options(['borrador' => 'Borrador', 'publicado' => 'Publicado'])->default('borrador')->required(),
                Select::make('availability_status')->label('Disponibilidad')->options(['disponible' => 'Disponible', 'apartado' => 'Apartado', 'vendido' => 'Vendido'])->default('disponible')->required(),
                Toggle::make('is_featured')->label('Destacado en Inicio')->default(false),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)->columns([
            TextColumn::make('code')->label('Clave')->searchable(),
            TextColumn::make('title')->label('Terreno')->searchable()->sortable(),
            TextColumn::make('zone.name')->label('Zona')->searchable(),
            TextColumn::make('price_mxn')->label('Precio')->money('MXN')->sortable(),
            TextColumn::make('area_m2')->label('m²')->numeric(decimalPlaces: 2)->sortable(),
            TextColumn::make('publication_status')->label('Publicación')->formatStateUsing(fn ($state) => ucfirst($state->value))->badge(),
            TextColumn::make('availability_status')->label('Disponibilidad')->formatStateUsing(fn ($state) => ucfirst($state->value))->badge(),
            IconColumn::make('is_featured')->label('Destacado')->boolean(),
        ])->defaultSort('created_at', 'desc')->filters([
            SelectFilter::make('publication_status')->label('Publicación')->options(['borrador' => 'Borrador', 'publicado' => 'Publicado']),
            SelectFilter::make('availability_status')->label('Disponibilidad')->options(['disponible' => 'Disponible', 'apartado' => 'Apartado', 'vendido' => 'Vendido']),
            TrashedFilter::make(),
        ])->recordActions([
            Action::make('public')->label('Ver ficha')->url(fn (Property $record) => route('properties.show', $record->slug))->openUrlInNewTab()->visible(fn (Property $record) => ! $record->trashed() && $record->publication_status->value === 'publicado'),
            EditAction::make(), DeleteAction::make(), RestoreAction::make(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListProperties::route('/'), 'create' => Pages\CreateProperty::route('/create'), 'edit' => Pages\EditProperty::route('/{record}/edit')];
    }
}
