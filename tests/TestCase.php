<?php

namespace Mdmnv\FilamentDatabaseViewer\Tests;

use Mdmnv\FilamentDatabaseViewer\DatabaseBrowserServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [DatabaseBrowserServiceProvider::class];
    }
}
