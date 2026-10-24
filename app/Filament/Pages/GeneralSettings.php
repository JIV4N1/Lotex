<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class GeneralSettings extends Page
{
    protected static ?string $title = 'Configuración general';

    protected static ?string $slug = 'configuracion-general';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.general-settings';

    public array $settings = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->is_admin && (bool) auth()->user()?->is_superadmin;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->settings = SiteSetting::current();
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $validated = $this->validate([
            'settings.footer_text' => 'required|string|max:250', 'settings.coverage' => 'required|string|max:150',
            'settings.contact_email' => 'nullable|email:rfc|max:254',
            'settings.contact_phone' => ['nullable', 'string', 'max:25', 'regex:/^\+?[0-9 ()-]{7,25}$/'],
            'settings.office_hours' => 'nullable|string|max:200',
        ]);
        SiteSetting::upsert([
            ['id' => 1, 'values' => json_encode(array_intersect_key($validated['settings'], SiteSetting::defaults()), JSON_THROW_ON_ERROR)],
        ], ['id'], ['values', 'updated_at']);
        Notification::make()->title('Configuración guardada')->success()->send();
    }
}
