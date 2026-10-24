<?php

namespace App\Services;

use App\Models\Municipality;
use App\Models\Property;
use App\Models\State;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class LotificationTransfer
{
    private function fields(): array
    {
        return array_values(array_diff((new Property)->getFillable(), ['zone_id', 'lotification_id']));
    }

    public function export(string $code): array
    {
        $parent = Property::where('code', $code)->where('is_lotification', true)->sole();
        $zone = $parent->zone;
        $pack = ['version' => 1, 'exported_at' => now()->toIso8601String(), 'location' => $zone ? [
            'state' => $zone->municipality->state->name, 'municipality' => $zone->municipality->name, 'zone' => $zone->name,
        ] : null, 'records' => []];
        foreach (collect([$parent])->concat($parent->lots()->orderBy('id')->get()) as $record) {
            $row = ['attributes' => array_intersect_key($record->attributesToArray(), array_flip($this->fields())), 'media' => []];
            foreach (['images', 'documents'] as $relation) {
                foreach ($record->$relation as $media) {
                    $bytes = Storage::disk('local')->get($media->path);
                    if ($bytes === null) {
                        throw new RuntimeException('Falta un archivo local. Exportación cancelada.');
                    }
                    $row['media'][] = ['relation' => $relation, 'metadata' => $media->only($relation === 'images' ? ['alt_text', 'position'] : ['title', 'type', 'issuer', 'issued_at', 'is_public']), 'sha256' => hash('sha256', $bytes), 'content' => base64_encode($bytes)];
                }
            }
            $pack['records'][] = $row;
        }

        return $pack;
    }

    public function validate(array $pack): void
    {
        Validator::make($pack, [
            'version' => 'required|in:1', 'records' => 'required|array|min:2|max:5000',
            'location' => 'nullable|array:state,municipality,zone',
            'location.state' => 'required_with:location|string|max:255',
            'location.municipality' => 'required_with:location|string|max:255',
            'location.zone' => 'required_with:location|string|max:255',
            'records.*.attributes' => 'required|array:'.implode(',', $this->fields()),
            'records.*.attributes.code' => 'required|string|max:64|distinct',
            'records.*.attributes.slug' => 'required|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|max:255|distinct',
            'records.*.attributes.title' => 'required|string|max:255',
            'records.*.attributes.publication_status' => 'required|in:borrador,publicado',
            'records.*.attributes.availability_status' => 'required|in:disponible,apartado,vendido',
            'records.*.attributes.is_lotification' => 'required|boolean',
            'records.*.attributes.is_featured' => 'required|boolean',
            'records.*.attributes.price_mxn' => 'nullable|numeric|min:0',
            'records.*.attributes.area_m2' => 'nullable|numeric|gt:0',
            'records.*.attributes.services' => 'nullable|array',
            'records.*.media' => 'present|array|max:50',
        ])->validate();
        $identities = [];
        foreach ($pack['records'] as $index => $row) {
            $a = $row['attributes'];
            if ((bool) $a['is_lotification'] !== ($index === 0)) {
                throw new RuntimeException('El paquete debe contener una lotificación seguida de sus lotes.');
            }
            if ($index > 0) {
                if (blank($a['block'] ?? null) || blank($a['lot_number'] ?? null)) {
                    throw new RuntimeException('Falta manzana o lote.');
                }
                $key = json_encode([$a['block'], $a['lot_number']]);
                if (isset($identities[$key])) {
                    throw new RuntimeException('Manzana/lote duplicado.');
                }
                $identities[$key] = true;
                if ($a['publication_status'] === 'publicado' && (! isset($a['price_mxn']) || ! isset($a['area_m2']))) {
                    throw new RuntimeException('Lote publicado incompleto.');
                }
            } elseif ($a['publication_status'] === 'publicado' && (empty($pack['location']) || blank($a['description'] ?? null))) {
                throw new RuntimeException('La lotificación publicada necesita ubicación y descripción.');
            }
            foreach ($row['media'] as $media) {
                $this->decodeMedia($media);
            }
        }
    }

    private function decodeMedia(array $media): array
    {
        $bytes = base64_decode($media['content'] ?? '', true);
        if (! is_string($bytes) || strlen($bytes) > 10 * 1024 * 1024 || ! hash_equals(hash('sha256', $bytes), (string) ($media['sha256'] ?? ''))) {
            throw new RuntimeException('Archivo corrupto o demasiado grande.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $extensions = ($media['relation'] ?? '') === 'images' ? ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'] : (($media['relation'] ?? '') === 'documents' ? ['application/pdf' => 'pdf'] : []);
        if (! isset($extensions[$mime])) {
            throw new RuntimeException('Tipo de archivo no permitido.');
        }
        Validator::make($media['metadata'] ?? [], $media['relation'] === 'images' ? [
            'alt_text' => 'required|string|max:255', 'position' => 'required|integer|min:0',
        ] : ['title' => 'required|string|max:255', 'type' => 'nullable|string|max:255', 'issuer' => 'nullable|string|max:255', 'issued_at' => 'nullable|date', 'is_public' => 'required|boolean'])->validate();

        return [$bytes, $extensions[$mime]];
    }

    private function conflicts(array $pack): void
    {
        $rows = collect($pack['records'])->pluck('attributes');
        if (Property::withTrashed()->whereIn('code', $rows->pluck('code'))->orWhereIn('slug', $rows->pluck('slug'))->orWhere('title', $rows->first()['title'])->exists()) {
            throw new RuntimeException('Ya existe una clave, enlace o lotificación con ese nombre. No se sobrescribió nada.');
        }
    }

    public function import(array $pack, bool $apply = false): ?Property
    {
        $this->validate($pack);
        $this->conflicts($pack);
        if (! $apply) {
            return null;
        }
        $written = [];
        try {
            return DB::transaction(function () use ($pack, &$written) {
                DB::select('select pg_advisory_xact_lock(20261023, 53)');
                $this->conflicts($pack);
                $zone = null;
                if ($location = $pack['location']) {
                    $find = function ($class, $attributes) {
                        $matches = $class::where($attributes)->get();
                        if ($matches->count() > 1) {
                            throw new RuntimeException('Ubicación ambigua: revisa los nombres duplicados.');
                        }

                        return $matches->first() ?? $class::create($attributes);
                    };
                    $state = $find(State::class, ['name' => $location['state']]);
                    $municipality = $find(Municipality::class, ['name' => $location['municipality'], 'state_id' => $state->id]);
                    $zone = $find(Zone::class, ['name' => $location['zone'], 'municipality_id' => $municipality->id]);
                }
                $parent = null;
                foreach ($pack['records'] as $index => $row) {
                    $record = Property::create($row['attributes'] + ['zone_id' => $index === 0 ? $zone?->id : null, 'lotification_id' => $parent?->id]);
                    $parent ??= $record;
                    foreach ($row['media'] as $media) {
                        [$bytes, $ext] = $this->decodeMedia($media);
                        $path = ($media['relation'] === 'images' ? 'property-images/' : 'property-documents/').Str::uuid().'.'.$ext;
                        $written[] = $path;
                        if (! Storage::disk('local')->put($path, $bytes)) {
                            throw new RuntimeException('No se pudo escribir un archivo.');
                        }
                        $allowed = $media['relation'] === 'images' ? ['alt_text', 'position'] : ['title', 'type', 'issuer', 'issued_at', 'is_public'];
                        $record->{$media['relation']}()->create(array_intersect_key($media['metadata'], array_flip($allowed)) + ['path' => $path]);
                    }
                }

                return $parent;
            });
        } catch (\Throwable $e) {
            foreach ($written as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }
}
