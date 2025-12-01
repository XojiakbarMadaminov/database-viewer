<?php

namespace Mdmnv\FilamentDatabaseViewer;

use Illuminate\Support\ServiceProvider;
use Mdmnv\FilamentDatabaseViewer\Filament\Pages\DatabaseBrowserPage;

class DatabaseBrowserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/database-browser.php', 'database-browser');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'database-browser');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'database-browser');

        $this->publishes([
            __DIR__ . '/../config/database-browser.php' => $this->app->configPath('database-browser.php'),
        ], 'database-browser-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => $this->app->resourcePath('views/vendor/database-browser'),
        ], 'database-browser-views');

        $this->publishes([
            __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/database-browser'),
        ], 'database-browser-translations');

        DatabaseBrowserPage::configureNavigation();
        DatabaseBrowserPage::configurePreviewLimits();
    }
}
