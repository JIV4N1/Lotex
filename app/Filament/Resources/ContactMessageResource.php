<?php

namespace App\Filament\Resources;

use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $modelLabel = 'mensaje';

    protected static ?string $pluralModelLabel = 'Mensajes';

    protected static ?string $slug = 'mensajes';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión comercial';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nombre')->disabled()->dehydrated(false),
            TextInput::make('phone')->label('Teléfono / WhatsApp')->disabled()->dehydrated(false),
            TextInput::make('email')->label('Correo')->disabled()->dehydrated(false),
            TextInput::make('reference')->label('Referencia')->afterStateHydrated(fn ($component, $record) => $component->state($record->property?->code ?? 'Consulta general'))->disabled()->dehydrated(false),
            Textarea::make('message')->label('Mensaje recibido')->rows(6)->disabled()->dehydrated(false)->columnSpanFull(),
            Select::make('status')->label('Estado')->options(['nuevo' => 'Nuevo', 'en_atencion' => 'En atención', 'resuelto' => 'Resuelto'])->required(),
            Textarea::make('notes')->label('Notas internas')->helperText('Estas notas no se envían al cliente.')->rows(4)->maxLength(10000)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)->columns([
            TextColumn::make('name')->label('Nombre')->searchable(), TextColumn::make('phone')->label('Teléfono')->searchable(),
            TextColumn::make('message')->label('Consulta')->limit(60)->searchable(),
            TextColumn::make('property.code')->label('Terreno')->placeholder('Consulta general'),
            TextColumn::make('status')->label('Estado')->formatStateUsing(fn ($state) => ['nuevo' => 'Nuevo', 'en_atencion' => 'En atención', 'resuelto' => 'Resuelto'][$state] ?? $state)->badge(),
            TextColumn::make('created_at')->label('Recibido')->dateTime('d/m/Y H:i', 'America/Mexico_City')->sortable(),
        ])->defaultSort('created_at', 'desc')->filters([SelectFilter::make('status')->label('Estado')->options(['nuevo' => 'Nuevo', 'en_atencion' => 'En atención', 'resuelto' => 'Resuelto'])])->recordActions([
            EditAction::make()->label('Leer y atender')->using(fn (ContactMessage $record, array $data) => tap($record)->update(['status' => $data['status'], 'notes' => $data['notes'] ?? null])),
            Action::make('whatsapp')->label('WhatsApp')->url(fn (ContactMessage $record) => 'https://wa.me/'.$record->phone)->openUrlInNewTab(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ContactMessageResource\Pages\ManageContactMessages::route('/')];
    }
}
