<?php

namespace App\Filament\Resources;

use App\Filament\Forms\PropertyFileUpload as FileUpload;
use App\Filament\Resources\PropertyResource\Pages;
use App\Models\Property;
use App\Services\UpdatePropertyBatch;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

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
            Section::make('Organización de la publicación')->schema([
                Toggle::make('is_lotification')->label('Es una lotificación (agrupa varios lotes)')->default(false)->live()->disabled(fn ($record) => $record?->lots()->withTrashed()->exists() ?? false),
                Select::make('lotification_id')->label('Pertenece a una lotificación')->default(fn () => request()->integer('lotification_id') ?: null)->options(fn () => Property::where('is_lotification', true)->orderBy('title')->pluck('title', 'id'))->searchable()->preload()->live()->visible(fn (Get $get) => ! $get('is_lotification'))->helperText('Vacío = terreno independiente. El lote hereda ubicación, fotografías y datos comunes; puedes especificar excepciones.'),
                TextInput::make('block')->label('Manzana')->maxLength(40)->required(fn (Get $get) => (bool) $get('lotification_id'))->visible(fn (Get $get) => (bool) $get('lotification_id')),
                TextInput::make('lot_number')->label('Número de lote')->maxLength(40)->required(fn (Get $get) => (bool) $get('lotification_id'))->visible(fn (Get $get) => (bool) $get('lotification_id'))->rules(fn (Get $get, $record) => [Rule::unique('properties', 'lot_number')->where('lotification_id', $get('lotification_id'))->where('block', $get('block'))->ignore($record?->id)]),
            ]),
            Section::make('Datos básicos')->description('Para empezar basta con un título. Guarda como borrador y completa la información cuando la tengas.')->columns(2)->schema([
                TextInput::make('title')->label('Nombre del terreno')->placeholder('Ej. Terreno campestre en Tehuacán')->required(fn (Get $get) => ! $get('lotification_id'))->visible(fn (Get $get) => ! $get('lotification_id'))->maxLength(255)->columnSpanFull(),
                Select::make('publication_status')->label('Publicación en la web')->options(['borrador' => 'Borrador · solo administradores', 'publicado' => 'Publicado'])->default('borrador')->required()->live()->helperText(fn (Get $get, $record) => $get('is_lotification')
                    ? 'La lotificación necesita zona, descripción y al menos un lote publicado y disponible para aparecer en el catálogo. Publicar esta ficha no publica automáticamente sus lotes. Lotes publicados y disponibles: '.($record?->lots()->where('publication_status', 'publicado')->where('availability_status', 'disponible')->count() ?? 0).'. Usa «Administrar lotes» en el listado de terrenos.'
                    : ($get('lotification_id') ? 'Para aparecer, publica este lote y su lotificación. El lote debe tener precio y superficie; solo los disponibles hacen aparecer la lotificación en el catálogo.' : 'Para publicar completa zona, descripción, precio y superficie. Guarda los cambios para que se apliquen.')),
                Select::make('availability_status')->label('Disponibilidad')->options(['disponible' => 'Disponible', 'apartado' => 'Apartado', 'vendido' => 'Vendido'])->default('disponible')->required()->helperText('Indica la situación comercial. Seleccionar Disponible no publica el terreno: cambia también Publicación en la web y guarda.'),
                Select::make('zone_id')->label('Zona / Municipio / Estado')
                    ->relationship('zone', 'name', modifyQueryUsing: fn (Builder $query) => $query->with('municipality.state'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name.' / '.$record->municipality->name.' / '.$record->municipality->state->name)
                    ->searchable()->preload()->required(fn (Get $get) => $get('publication_status') === 'publicado' && ! $get('lotification_id'))->visible(fn (Get $get) => ! $get('lotification_id'))->helperText('Selecciona una ubicación existente. Si falta, créala en Estados, Municipios y Zonas; puedes guardar el borrador primero.')->columnSpanFull(),
                TextInput::make('price_mxn')->label('Precio total (MXN)')->placeholder('Pendiente de confirmar')->numeric()->minValue(0)->maxValue(99999999999999.99)->step('0.01')->required(fn (Get $get) => $get('publication_status') === 'publicado' && ! $get('is_lotification'))->visible(fn (Get $get) => ! $get('is_lotification')),
                TextInput::make('area_m2')->label('Superficie (m²)')->placeholder('Pendiente de confirmar')->numeric()->minValue(0.01)->maxValue(999999999999.99)->step('0.01')->required(fn (Get $get) => $get('publication_status') === 'publicado' && ! $get('is_lotification'))->visible(fn (Get $get) => ! $get('is_lotification')),
                Textarea::make('description')->label('Descripción para el visitante')->placeholder('Describe el entorno y lo que conoces del terreno. No es necesario prometer servicios ni usos que no estén confirmados.')->required(fn (Get $get) => $get('publication_status') === 'publicado' && ! $get('lotification_id'))->rows(4)->maxLength(20000)->columnSpanFull(),
            ]),
            Section::make('Cómo llegar y medidas adicionales')->description('Opcional. Puedes dejar estos campos vacíos si aún no tienes la información.')->collapsed()->columns(2)->schema([
                TextInput::make('frontage_m')->label('Frente (m)')->numeric()->minValue(0.01)->maxValue(9999999999.99)->step('0.01'),
                TextInput::make('depth_m')->label('Fondo (m)')->numeric()->minValue(0.01)->maxValue(9999999999.99)->step('0.01'),
                Textarea::make('reference_address')->label('Dirección de referencia')->maxLength(2000)->columnSpanFull(),
                TextInput::make('latitude')->label('Latitud')->numeric()->minValue(-90)->maxValue(90)->step('0.0000001')->requiredWith('longitude'),
                TextInput::make('longitude')->label('Longitud')->numeric()->minValue(-180)->maxValue(180)->step('0.0000001')->requiredWith('latitude'),
            ]),
            Section::make('Características del terreno campestre')->description('Opcional. Describe solo lo que conoces; dejar un campo vacío significa que falta confirmar ese dato.')->collapsed()->columns(2)->schema([
                TagsInput::make('services')->label('Servicios confirmados')->helperText('Ej. agua disponible o electricidad a pie de terreno. Escribe cada dato y pulsa Enter. No implica que estén contratados.')->columnSpanFull(),
                Textarea::make('access')->label('Cómo es el acceso')->placeholder('Ej. camino de terracería; acceso en vehículo')->maxLength(5000),
                Textarea::make('topography')->label('Cómo es el terreno')->placeholder('Ej. plano, con pendiente o irregular')->maxLength(5000),
                Textarea::make('land_use')->label('Uso de suelo confirmado')->helperText('La descripción «campestre» no acredita un permiso de construcción. Déjalo vacío si no está confirmado.')->maxLength(5000),
                Textarea::make('documentary_summary')->label('Resumen documental')->maxLength(5000),
                Textarea::make('payment_terms')->label('Condiciones de pago')->maxLength(5000)->columnSpanFull(),
            ]),
            Section::make('Fotografías')->description('La primera imagen ordenada será la portada. Reordena, edita el texto alternativo o quita fotografías.')->schema([
                Repeater::make('images')->label('Galería')->relationship()->orderColumn('position')->defaultItems(0)->maxItems(30)->schema([
                    FileUpload::make('path')->label('Fotografía')->disk('local')->directory('property-images')->visibility('private')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->preventFilePathTampering()->required(),
                    \Filament\Forms\Components\Toggle::make('is_site_plan')->label('Usar como plano de referencia público')->helperText('Sube una versión limpia, sin datos personales ni información que no deba publicarse. Si marcas varias, se mostrará la primera según el orden de la galería.'),
                    TextInput::make('alt_text')->label('Descripción de la imagen')->required()->maxLength(255),
                ])->addActionLabel('Añadir fotografía')->collapsible(),
            ]),
            Section::make('Documentos y planos')->collapsed()->description('Opcional. PDF hasta 10 MB. Privados por defecto: autoriza solo versiones revisadas sin datos personales reservados.')->schema([
                Repeater::make('documents')->label('Documentos')->relationship()->defaultItems(0)->maxItems(20)->schema([
                    TextInput::make('title')->label('Nombre del documento')->required()->maxLength(255),
                    FileUpload::make('path')->label('Archivo PDF')->disk('local')->directory('property-documents')->visibility('private')->acceptedFileTypes(['application/pdf'])->maxSize(10240)->openable()->downloadable()->preventFilePathTampering()->required(),
                    TextInput::make('type')->label('Tipo (ej. plano, escritura)')->maxLength(255),
                    TextInput::make('issuer')->label('Emisor')->maxLength(255),
                    DatePicker::make('issued_at')->label('Fecha de emisión'),
                    Toggle::make('is_public')->label('Autorizar consulta pública de esta versión revisada')->default(false)->helperText('Solo se muestra con el terreno publicado. No certifica validez jurídica.'),
                ])->addActionLabel('Añadir documento')->collapsible(),
            ]),
            Section::make('Referencia y enlace')->description('Se generan automáticamente al guardar. Solo completa estos campos si necesitas una referencia propia.')->collapsed()->schema([
                TextInput::make('code')->label('Clave única (opcional)')->maxLength(64)->unique(ignoreRecord: true),
                TextInput::make('slug')->label('Enlace de la ficha (opcional)')->maxLength(255)->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true)->helperText('Ejemplo: terreno-tehuacan-centro. Si ya existe un enlace, cambiarlo deja de funcionar en su dirección anterior.'),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)->columns([
            TextColumn::make('code')->label('Clave')->searchable(),
            TextColumn::make('lotification.title')->label('Lotificación')->placeholder('Independiente / principal'),
            TextColumn::make('block')->label('Manzana'),
            TextColumn::make('lot_number')->label('Lote'),
            TextColumn::make('title')->label('Terreno')->searchable()->sortable(),
            TextColumn::make('zone.name')->label('Zona')->placeholder('Por completar')->searchable(),
            TextColumn::make('price_mxn')->label('Precio')->placeholder('Por completar')->money('MXN')->sortable(),
            TextColumn::make('area_m2')->label('m²')->placeholder('Por completar')->numeric(decimalPlaces: 2)->sortable(),
            TextColumn::make('publication_status')->label('Publicación')->formatStateUsing(fn ($state) => ucfirst($state->value))->badge(),
            TextColumn::make('availability_status')->label('Disponibilidad')->formatStateUsing(fn ($state) => ucfirst($state->value))->badge(),
        ])->defaultSort('created_at', 'desc')->paginationPageOptions([25, 50, 100])->toolbarActions([
            BulkAction::make('availability')->label('Cambiar disponibilidad')->requiresConfirmation()
                ->modalDescription('Se aplicará a todos los registros seleccionados. No cambia su publicación en la web.')
                ->schema([Select::make('value')->label('Nueva disponibilidad')->options(['disponible' => 'Disponible', 'apartado' => 'Apartado', 'vendido' => 'Vendido'])->required()])
                ->action(fn (Collection $records, array $data) => app(UpdatePropertyBatch::class)->update(Filament::auth()->user(), $records->modelKeys(), 'availability_status', $data['value']))->deselectRecordsAfterCompletion(),
            BulkAction::make('publication')->label('Cambiar publicación')->requiresConfirmation()
                ->modalDescription('Publica primero la lotificación. Todos los seleccionados deben tener sus datos obligatorios completos; si uno falla, no se modifica ninguno.')
                ->schema([Select::make('value')->label('Publicación')->options(['borrador' => 'Borrador', 'publicado' => 'Publicado'])->required()])
                ->action(fn (Collection $records, array $data) => app(UpdatePropertyBatch::class)->update(Filament::auth()->user(), $records->modelKeys(), 'publication_status', $data['value']))->deselectRecordsAfterCompletion(),
        ])->filters([
            SelectFilter::make('block')->label('Manzana')->options(fn () => Property::whereNotNull('block')->distinct()->orderBy('block')->pluck('block', 'block')),
            SelectFilter::make('lotification_id')->label('Lotificación')->options(fn () => Property::where('is_lotification', true)->orderBy('title')->pluck('title', 'id')),
            SelectFilter::make('publication_status')->label('Publicación')->options(['borrador' => 'Borrador', 'publicado' => 'Publicado']),
            SelectFilter::make('availability_status')->label('Disponibilidad')->options(['disponible' => 'Disponible', 'apartado' => 'Apartado', 'vendido' => 'Vendido']),
            TrashedFilter::make(),
        ])->recordActions([
            Action::make('public')->label('Ver ficha')->url(fn (Property $record) => route('properties.show', $record->slug))->openUrlInNewTab()->visible(fn (Property $record) => ! $record->trashed() && $record->publication_status->value === 'publicado'),
            Action::make('addLot')->label('Añadir lote')->url(fn (Property $record) => static::getUrl('create', ['lotification_id' => $record->id]))->visible(fn (Property $record) => $record->is_lotification && ! $record->trashed()),
            Action::make('viewLots')->label('Administrar lotes')->url(fn (Property $record) => static::getUrl('index', ['filters' => ['lotification_id' => ['value' => $record->id]]]))->visible(fn (Property $record) => $record->is_lotification && ! $record->trashed()),
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
