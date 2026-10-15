<?php

namespace App\Filament\Resources;

use App\Enums\VisitStatus;
use App\Models\User;
use App\Models\VisitRequest;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class VisitRequestResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Gestión comercial';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 2;

    protected static ?string $model = VisitRequest::class;

    protected static ?string $modelLabel = 'interesado';

    protected static ?string $pluralModelLabel = 'Interesados';

    protected static ?string $slug = 'interesados';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Cliente')->disabled()->dehydrated(false),
            TextInput::make('phone')->label('WhatsApp')->disabled()->dehydrated(false),
            TextInput::make('property_reference')->label('Terreno')->afterStateHydrated(fn ($component, $record) => $component->state($record->property->code.' · '.$record->property->title))->disabled()->dehydrated(false),
            Hidden::make('lock_version'),
            Select::make('assigned_to')->label('Responsable')->placeholder('Sin asignar')->options(fn () => User::where('is_admin', true)->orderBy('name')->pluck('name', 'id'))->searchable()->rules([Rule::exists('users', 'id')->where('is_admin', true)])->required(fn (Get $get) => in_array($get('status'), ['contactado', 'cita_confirmada'])),
            Select::make('status')->label('Estado')->options(VisitStatus::options())->required()->live(),
            DateTimePicker::make('appointment_at')->label('Cita acordada (hora de Ciudad de México)')->timezone('America/Mexico_City')->seconds(false)->required(fn (Get $get) => $get('status') === 'cita_confirmada')->helperText('Registra el horario después de acordarlo con el cliente.'),
            Textarea::make('notes')->label('Notas de seguimiento')->rows(5)->maxLength(10000)->columnSpanFull(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)->columns([
            TextColumn::make('name')->label('Cliente')->searchable(),
            TextColumn::make('phone')->label('WhatsApp')->searchable(),
            TextColumn::make('property.code')->label('Clave')->searchable(),
            TextColumn::make('property.title')->label('Terreno')->limit(35)->searchable(),
            TextColumn::make('status')->label('Estado')->formatStateUsing(fn ($state) => VisitStatus::options()[$state->value])->badge(),
            TextColumn::make('assignee.name')->label('Responsable')->placeholder('Sin asignar'),
            TextColumn::make('created_at')->label('Solicitud')->dateTime('d/m/Y H:i', 'America/Mexico_City')->sortable(),
            TextColumn::make('appointment_at')->label('Cita')->dateTime('d/m/Y H:i', 'America/Mexico_City')->placeholder('Por acordar')->sortable(),
            TextColumn::make('updatedBy.name')->label('Actualizado por')->toggleable(isToggledHiddenByDefault: true),
        ])->defaultSort('created_at', 'desc')->filters([
            SelectFilter::make('status')->label('Estado')->options(VisitStatus::options()),
            Filter::make('unassigned')->label('Sin asignar')->query(fn (Builder $query) => $query->whereNull('assigned_to')),
            SelectFilter::make('assigned_to')->label('Responsable')->options(fn () => User::where('is_admin', true)->pluck('name', 'id')),
        ])->recordActions([
            EditAction::make()->label('Seguimiento'),
            Action::make('whatsapp')->label('WhatsApp')->url(fn (VisitRequest $record) => 'https://wa.me/'.$record->phone.'?text='.rawurlencode('Hola '.$record->name.', te contactamos por tu solicitud de visita al terreno '.$record->property->code.'. ¿Qué horario te conviene?'))->openUrlInNewTab(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => VisitRequestResource\Pages\ListVisitRequests::route('/'), 'edit' => VisitRequestResource\Pages\EditVisitRequest::route('/{record}/edit')];
    }
}
