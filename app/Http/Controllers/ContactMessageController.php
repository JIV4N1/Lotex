<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ContactMessageController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'phone' => ['required', 'string', 'max:30', 'regex:/^[+0-9\s()\-]+$/'],
            'email' => ['nullable', 'email', 'max:255'], 'message' => ['required', 'string', 'min:10', 'max:5000'],
            'property_slug' => ['nullable', 'string', 'max:200'], 'submission_token' => ['required', 'uuid'], 'website' => ['nullable', 'max:0'],
        ]);
        $phone = preg_replace('/\D/', '', $data['phone']);
        if (strlen($phone) === 10) {
            $phone = '52'.$phone;
        }
        if (str_starts_with($phone, '521') && strlen($phone) === 13) {
            $phone = '52'.substr($phone, 3);
        }
        if (! preg_match('/^52[1-9][0-9]{9}$/', $phone) && ! (str_starts_with(trim($data['phone']), '+') && preg_match('/^[1-9][0-9]{7,14}$/', $phone))) {
            throw ValidationException::withMessages(['phone' => 'Escribe 10 dígitos para México o incluye + y código de país.']);
        }
        $property = null;
        if (! empty($data['property_slug'])) {
            $property = Property::publiclyVisible()->where('slug', $data['property_slug'])->first();
            if (! $property) {
                throw ValidationException::withMessages(['property_slug' => 'El terreno ya no está publicado. Envía una consulta general desde Contacto.']);
            }
        }
        ContactMessage::firstOrCreate(['submission_token' => $data['submission_token']], [
            'name' => trim($data['name']), 'phone' => $phone, 'email' => $data['email'] ?? null, 'message' => trim($data['message']),
            'property_id' => $property?->id, 'status' => 'nuevo',
        ]);

        return redirect()->route('contact')->with('contact_sent', true);
    }
}
