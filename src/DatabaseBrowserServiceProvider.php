<?php

namespace Mdmnv\FilamentDatabaseViewer;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DatabaseBrowserServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-database-viewer';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)->hasViews();
    }

    public function packageBooted(): void {}
}
