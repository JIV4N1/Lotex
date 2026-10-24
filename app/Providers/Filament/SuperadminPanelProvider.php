<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AdvisorSupervision;
use App\Filament\Pages\GeneralSettings;
use Filament\Panel;

class SuperadminPanelProvider extends AdminPanelProvider
{
    protected function panelId(): string
    {
        return 'superadmin';
    }

    public function panel(Panel $panel): Panel
    {
        return parent::panel($panel)->default(false)->path('superadmin')->authGuard('superadmin')
            ->brandName('Horizonte · Superadmin')
            ->pages([GeneralSettings::class, AdvisorSupervision::class]);
    }
}
