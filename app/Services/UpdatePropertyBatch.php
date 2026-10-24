<?php

namespace App\Services;

use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdatePropertyBatch
{
    public function update(User $user, array $ids, string $field, string $value): void
    {
        $allowed = ['availability_status' => ['disponible', 'apartado', 'vendido'], 'publication_status' => ['borrador', 'publicado']];
        abort_unless(isset($allowed[$field]) && in_array($value, $allowed[$field], true), 422);

        DB::transaction(function () use ($user, $ids, $field, $value) {
            $records = Property::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            if ($records->count() !== count(array_unique($ids))) {
                throw ValidationException::withMessages(['value' => 'La selección cambió o incluye registros eliminados. Actualiza el listado.']);
            }
            foreach ($records as $record) {
                Gate::forUser($user)->authorize('update', $record);
                if ($field === 'publication_status' && $value === 'publicado') {
                    $complete = $record->lotification_id
                        ? $record->lotification?->publication_status->value === 'publicado'
                        : $record->zone_id && filled($record->description);
                    if (! $record->is_lotification) {
                        $complete = $complete && $record->price_mxn !== null && $record->area_m2 > 0;
                    }
                    if (! $complete) {
                        throw ValidationException::withMessages(['value' => "{$record->code}: completa los datos obligatorios y publica primero su lotificación. No se modificó la selección."]);
                    }
                }
                $record->update([$field => $value]);
            }
        });
    }
}
