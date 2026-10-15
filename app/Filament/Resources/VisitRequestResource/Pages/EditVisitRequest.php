<?php

namespace App\Filament\Resources\VisitRequestResource\Pages;

use App\Filament\Resources\VisitRequestResource;
use App\Models\VisitRequest;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditVisitRequest extends EditRecord
{
    protected static string $resource = VisitRequestResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $current = VisitRequest::lockForUpdate()->findOrFail($record->id);
            if ($current->lock_version !== (int) ($data['lock_version'] ?? -1)) {
                throw ValidationException::withMessages(['data.status' => 'Otra persona actualizó esta solicitud. Recarga la página antes de guardar.']);
            }
            $current->update([
                'assigned_to' => $data['assigned_to'] ?? null,
                'status' => $data['status'], 'appointment_at' => $data['appointment_at'] ?? null,
                'notes' => $data['notes'] ?? null, 'updated_by' => auth()->id(),
                'lock_version' => $current->lock_version + 1,
            ]);
            $this->data['lock_version'] = $current->lock_version;

            return $current;
        });
    }
}
