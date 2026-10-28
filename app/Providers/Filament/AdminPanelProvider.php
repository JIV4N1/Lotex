<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Http\Middleware\SetAdminLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    protected function panelId(): string
    {
        return 'admin';
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id($this->panelId())
            ->path('admin')
            ->login()
            ->brandName('Horizonte · Administración')
            ->colors(['primary' => '#245440'])
            ->darkMode()
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups(['Gestión comercial', 'Ubicaciones', 'Administración'])
            ->renderHook('panels::head.end', fn () => view('filament.admin-styles'))
            ->renderHook('panels::page.start', fn () => view('filament.back-navigation'))
            ->pages([Dashboard::class, \App\Filament\Pages\LotMapEditor::class])->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->middleware([SetAdminLocale::class], isPersistent: true)
            ->authMiddleware([Authenticate::class], isPersistent: true);
    }
}
