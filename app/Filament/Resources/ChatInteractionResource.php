<?php

namespace App\Filament\Resources;

use App\Models\ChatInteraction;
use Carbon\CarbonImmutable;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChatInteractionResource extends Resource
{
    protected static ?string $model = ChatInteraction::class;

    protected static ?string $slug = 'chatbot';

    protected static ?string $modelLabel = 'consulta del chatbot';

    protected static ?string $pluralModelLabel = 'Chatbot · Gemini';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión comercial';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->retained();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('question')->label('Pregunta del visitante')->columnSpanFull(),
            TextEntry::make('answer')->label('Respuesta de Horizonte')->placeholder('No se pudo completar la respuesta.')->columnSpanFull(),
            TextEntry::make('property_slug')->label('Ficha abierta')->placeholder('Sin ficha de terreno'),
            TextEntry::make('model')->label('Modelo'),
            TextEntry::make('status')->label('Resultado')->formatStateUsing(fn ($state) => $state === 'success' ? 'Respondida' : 'Error'),
            TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s', 'America/Mexico_City'),
            TextEntry::make('input_tokens')->label('Tokens de entrada')->placeholder('No reportados'),
            TextEntry::make('output_tokens')->label('Tokens de salida')->placeholder('No reportados'),
            TextEntry::make('total_tokens')->label('Tokens totales')->placeholder('No reportados'),
            TextEntry::make('http_status')->label('Estado HTTP del proveedor')->placeholder('Sin respuesta HTTP'),
            TextEntry::make('duration_ms')->label('Tiempo de respuesta (ms)'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)->columns([
            TextColumn::make('question')->label('Pregunta')->limit(70)->wrap(),
            TextColumn::make('status')->label('Resultado')->badge()->formatStateUsing(fn ($state) => $state === 'success' ? 'Respondida' : 'Error')->color(fn ($state) => $state === 'success' ? 'success' : 'danger'),
            TextColumn::make('total_tokens')->label('Tokens')->numeric()->placeholder('No reportados')->sortable(),
            TextColumn::make('duration_ms')->label('Tiempo')->formatStateUsing(fn ($state) => number_format($state / 1000, 1).' s')->sortable(),
            TextColumn::make('property_slug')->label('Ficha abierta')->limit(30)->placeholder('Consulta general')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i', 'America/Mexico_City')->sortable(),
        ])->defaultSort('created_at', 'desc')->filters([
            SelectFilter::make('status')->label('Resultado')->options(['success' => 'Respondida', 'error' => 'Error']),
            Filter::make('period')->label('Fechas')->schema([
                DatePicker::make('from')->label('Desde')->native()->maxDate(now()),
                DatePicker::make('until')->label('Hasta')->native()->maxDate(now())->afterOrEqual('from'),
            ])->query(function (Builder $query, array $data) {
                // Dates are interpreted in Mexico City, then compared with UTC timestamps.
                foreach (['from' => '>=', 'until' => '<='] as $field => $operator) {
                    if (isset($data[$field]) && is_string($data[$field]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data[$field])) {
                        try {
                            $date = CarbonImmutable::createFromFormat('!Y-m-d', $data[$field], 'America/Mexico_City');
                            $query->where('created_at', $operator, ($field === 'from' ? $date->startOfDay() : $date->endOfDay())->utc());
                        } catch (\Throwable) {
                            $query->whereRaw('1 = 0');
                        }
                    }
                }

                return $query;
            }),
        ])->recordActions([ViewAction::make()->label('Leer consulta')->modalHeading('Consulta del chatbot')->modalWidth('2xl')])
            ->emptyStateHeading('Todavía no hay consultas registradas')
            ->emptyStateDescription('Las nuevas preguntas aparecerán aquí cuando se utilice el chatbot. No se recuperan conversaciones anteriores.')
            ->paginationPageOptions([10, 25, 50])->defaultPaginationPageOption(10);
    }

    public static function getPages(): array
    {
        return ['index' => ChatInteractionResource\Pages\ListChatInteractions::route('/')];
    }
}
