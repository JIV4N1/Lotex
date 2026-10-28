<?php
namespace App\Filament\Pages;

use App\Models\Property;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LotMapEditor extends Page
{
    protected static ?string $title = 'Trazador de lotificaciones';
    protected static ?string $slug = 'trazador';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestión comercial';
    protected string $view = 'filament.pages.lot-map-editor';

    public static function canAccess(): bool { return (bool) auth()->user()?->is_admin; }

    public function loadMap(int $id): array
    {
        abort_unless(static::canAccess(), 403);
        $parent = Property::where('is_lotification', true)->findOrFail($id);
        $path = 'lot-maps/'.$parent->id.'.json';
        return ['lots' => $parent->lots()->orderBy('block')->orderBy('lot_number')->get(['id','block','lot_number','title','area_m2','frontage_m','depth_m'])->toArray(),
            'map' => Storage::disk('local')->exists($path) ? json_decode(Storage::disk('local')->get($path), true) : null];
    }

    public function detectLots(int $id, string $image): array
    {
        abort_unless(static::canAccess(), 403);
        $parent = Property::where('is_lotification', true)->findOrFail($id);
        $key = 'lot-tracing:'.auth()->id();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 3)) return ['error'=>'Espera unos minutos antes de solicitar otro análisis.'];
        \Illuminate\Support\Facades\RateLimiter::hit($key, 600);
        $lots = $parent->lots()->get(['id','block','lot_number']);
        try {
            $detected = app(\App\Services\GeminiLotTracer::class)->detect($image, $lots->map(fn($lot)=>['block'=>$lot->block,'number'=>$lot->lot_number])->all());
        } catch (\RuntimeException $e) { return ['error'=>$e->getMessage()]; }
        $known = $lots->keyBy(fn($lot)=>$lot->block.':'.$lot->lot_number);
        $result = []; $seen = []; $rejected = 0;
        foreach ($detected as $shape) {
            $identity = $shape['block'].':'.$shape['number'];
            if (! $known->has($identity) || isset($seen[$identity])) { $rejected++; continue; }
            if (! \App\Services\LotPolygon::valid($shape['points'])) { $rejected++; continue; }
            $seen[$identity] = true;
            $result[] = ['id'=>$known[$identity]->id,'points'=>$shape['points']];
        }
        return ['lots'=>$result,'rejected'=>$rejected,'missing'=>$lots->count()-count($result), 'missing_lots'=>$lots->reject(fn($lot)=>isset($seen[$lot->block.':'.$lot->lot_number]))->map(fn($lot)=>'M'.$lot->block.' · L'.$lot->lot_number)->values()->all()];
    }

    public function saveMap(int $id, array $map): void
    {
        abort_unless(static::canAccess(), 403);
        $parent = Property::where('is_lotification', true)->findOrFail($id);
        Validator::make($map, [
            'width'=>'required|integer|min:1|max:10000','height'=>'required|integer|min:1|max:10000',
            'lots'=>'required|array|min:1|max:1000','lots.*.id'=>'required|integer|distinct',
            'lots.*.points'=>'required|array|min:3|max:100', 'lots.*.points.*'=>'required|array|size:2',
            'lots.*.points.*.*'=>'required|numeric|min:0|max:10000',
        ])->validate();
        $records = $parent->lots()->get()->keyBy('id');
        $shapes = [];
        foreach ($map['lots'] as $shape) {
            abort_unless($records->has($shape['id']), 422);
            foreach ($shape['points'] as $point) abort_if($point[0] > $map['width'] || $point[1] > $map['height'], 422);
            abort_unless(\App\Services\LotPolygon::valid($shape['points']), 422);
            $lot = $records[$shape['id']];
            $shapes[] = ['block'=>$lot->block, 'number'=>$lot->lot_number, 'id'=>$lot->id, 'points'=>$shape['points']];
        }
        Storage::disk('local')->put('lot-maps/'.$parent->id.'.json', json_encode(['width'=>$map['width'],'height'=>$map['height'],'viewBox'=>'0 0 '.$map['width'].' '.$map['height'], 'lots'=>$shapes], JSON_THROW_ON_ERROR));
    }

    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);
        return ['groups'=>Property::where('is_lotification',true)->orderBy('title')->get(['id','title'])];
    }
}
