<?php
namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use App\Models\Property;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Table;
use Livewire\Attributes\Locked;

class ListLots extends ListRecords
{
    protected static string $resource = PropertyResource::class;
    #[Locked]
    public int $lotificationId;

    public function mount(int|string|null $parent = null): void
    {
        $this->lotificationId = Property::where('is_lotification', true)->findOrFail($parent)->id;
        parent::mount();
    }

    public function getTitle(): string { return 'Lotes · '.Property::findOrFail($this->lotificationId)->title; }

    public function table(Table $table): Table
    {
        return parent::table($table)->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query)=>$query->where('lotification_id', $this->lotificationId))
            ->defaultSort(fn(\Illuminate\Database\Eloquent\Builder $query)=>$query->orderByRaw("CASE WHEN block ~ '^[0-9]+$' THEN block::numeric END NULLS LAST")->orderBy('block')->orderByRaw("CASE WHEN lot_number ~ '^[0-9]+$' THEN lot_number::numeric END NULLS LAST")->orderBy('lot_number'));
    }

    public function getTabs(): array
    {
        $tabs = ['all'=>Tab::make('Todas las manzanas')];
        foreach (Property::where('lotification_id',$this->lotificationId)->distinct()->pluck('block')->sort(SORT_NATURAL) as $block) {
            $tabs['block-'.$block] = Tab::make('Manzana '.$block)->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query)=>$query->where('block',$block));
        }
        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')->label('Lotificaciones')->url(PropertyResource::getUrl('index')),
            Action::make('editParent')->label('Editar lotificación')->url(PropertyResource::getUrl('edit',['record'=>$this->lotificationId])),
            Action::make('add')->label('Agregar lote')->url(PropertyResource::getUrl('create',['lotification_id'=>$this->lotificationId])),
            Action::make('map')->label('Ver mapa')->url(\App\Filament\Pages\LotMapEditor::getUrl(['lotification'=>$this->lotificationId])),
        ];
    }
}
