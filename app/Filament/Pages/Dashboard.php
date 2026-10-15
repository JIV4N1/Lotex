<?php

namespace App\Filament\Pages;

use App\Models\Property;
use App\Models\VisitRequest;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $title = 'Resumen';

    protected string $view = 'filament.pages.dashboard';

    protected function getViewData(): array
    {
        return [
            'published' => Property::where('publication_status', 'publicado')->count(),
            'drafts' => Property::where('publication_status', 'borrador')->count(),
            'unassigned' => VisitRequest::whereNull('assigned_to')->where('status', '!=', 'cerrado')->count(),
            'appointments' => VisitRequest::where('status', 'cita_confirmada')->where('appointment_at', '>=', now())->count(),
            'recent' => VisitRequest::with(['property', 'assignee'])->latest()->limit(5)->get(),
        ];
    }
}
