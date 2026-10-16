<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\VisitRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class VisitRequestController extends Controller
{
    public function store(Request $request, string $slug)
    {
        $property = Property::where('publication_status', 'publicado')->where('slug', $slug)->firstOrFail();
        abort_if($property->availability_status->value === 'vendido', 409, 'Este terreno ya fue vendido.');
        $input = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[+0-9\s()\-]+$/'],
            'submission_token' => ['required', 'uuid'],
            'website' => ['nullable', 'max:0'],
        ]);
        $phone = preg_replace('/\D/', '', $input['phone']);
        if (strlen($phone) === 10) {
            $phone = '52'.$phone;
        }
        if (str_starts_with($phone, '521') && strlen($phone) === 13) {
            $phone = '52'.substr($phone, 3);
        }
        if (! preg_match('/^52[1-9][0-9]{9}$/', $phone) && ! (str_starts_with(trim($input['phone']), '+') && preg_match('/^[1-9][0-9]{7,14}$/', $phone))) {
            throw ValidationException::withMessages(['phone' => 'Escribe los 10 dígitos de tu WhatsApp mexicano o un número internacional con + y código de país.']);
        }
        // Serialize submissions for the same number/property to avoid double clicks and concurrent duplicates.
        Cache::lock('visit-'.hash('sha256', $property->id.':'.$phone), 10)->block(3, function () use ($property, $input, $phone) {
            $duplicate = VisitRequest::where('property_id', $property->id)->where('phone', $phone)->where('created_at', '>=', now()->subDay())->exists();
            if (! $duplicate) {
                $saved = VisitRequest::firstOrCreate(['submission_token' => $input['submission_token']], [
                    'property_id' => $property->id, 'name' => trim($input['name']), 'phone' => $phone,
                    'status' => 'nuevo', 'assigned_to' => null,
                ]);
                if ($saved->property_id !== $property->id || $saved->phone !== $phone) {
                    throw ValidationException::withMessages(['submission_token' => 'Recarga la ficha del terreno y vuelve a enviar tu solicitud.']);
                }
            }
        });

        return redirect()->to(route('properties.show', $slug).'#solicitar-visita')->with('visit_requested', true);
    }
}
