<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\VisitRequest;
use Filament\Pages\Page;

class AdvisorSupervision extends Page
{
    protected static ?string $title = 'Supervisión de asesores';

    protected static ?string $slug = 'supervision-asesores';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión comercial';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.pages.advisor-supervision';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->is_admin && (bool) auth()->user()?->is_superadmin;
    }

    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        return [
            'unassigned' => VisitRequest::whereNull('assigned_to')->where('status', '!=', 'cerrado')->count(),
            'advisors' => User::where(fn ($q) => $q->where('is_admin', true)->orWhereHas('assignedVisits'))->withCount([
                'assignedVisits',
                'assignedVisits as new_count' => fn ($q) => $q->where('status', 'nuevo'),
                'assignedVisits as contacted_count' => fn ($q) => $q->where('status', 'contactado'),
                'assignedVisits as upcoming_count' => fn ($q) => $q->where('status', 'cita_confirmada')->where('appointment_at', '>=', now()),
                'assignedVisits as past_count' => fn ($q) => $q->where('status', 'cita_confirmada')->where('appointment_at', '<', now()),
                'assignedVisits as closed_count' => fn ($q) => $q->where('status', 'cerrado'),
            ])->orderBy('name')->get(),
        ];
    }
}
