<?php

namespace App\Providers\Filament;

use App\AppPanel\Pages\CustomLoginPage;
use App\AppPanel\Widgets\StatsInventoryOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->maxContentWidth(Width::Full)
            ->subNavigationPosition(SubNavigationPosition::Top)
            ->brandName('Arham Stock System')
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('3rem')
            ->databaseNotifications()
            ->login()
            ->colors([
                'danger' => Color::Rose,       // untuk error / delete
                // 'gray' => Color::hex('#0B0B0B'),       // tetap abu-abu netral
                'info' => Color::Blue,       // tetap biru (informasi)
                'primary' => Color::hex('#D6BB83'), // emas-beige dari logo
                'success' => Color::Emerald,    // hijau sukses
                'warning' => Color::Amber,
            ])
            ->viteTheme('resources/css/filament/app/theme.css')
            ->discoverResources(in: app_path('AppPanel/Resources'), for: 'App\\AppPanel\\Resources')
            ->discoverPages(in: app_path('AppPanel/Pages'), for: 'App\\AppPanel\\Pages')
            ->discoverClusters(in: app_path('AppPanel/Clusters'), for: 'App\\AppPanel\\Clusters')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('AppPanel/Widgets'), for: 'App\AppPanel\Widgets')
            ->widgets([
                    // AccountWidget::class,
                    // FilamentInfoWidget::class,
                StatsInventoryOverview::class,
            ])
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
