<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['values'];

    protected function casts(): array
    {
        return ['values' => 'array'];
    }

    public static function defaults(): array
    {
        return ['footer_text' => 'El espacio donde comienzan tus ideas.', 'coverage' => 'Lotificaciones en Venta · Puebla, México', 'contact_email' => '', 'contact_phone' => '', 'office_hours' => ''];
    }

    public static function current(): array
    {
        return array_replace(self::defaults(), self::find(1)?->values ?? []);
    }
}
