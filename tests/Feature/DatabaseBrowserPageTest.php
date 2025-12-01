<?php

use Mdmnv\FilamentDatabaseViewer\Filament\Pages\DatabaseBrowserPage;

it('filters available tables using configuration', function () {
    config([
        'database-browser.tables.allowed' => ['users', 'orders'],
        'database-browser.tables.limit' => 10,
    ]);

    $page = new class extends DatabaseBrowserPage {
        protected array $mockTables = ['users', 'failed_jobs', 'orders', 'password_resets'];

        public function exposeFetch(): array
        {
            return $this->fetchAvailableTables();
        }

        protected function resolveTablesFromDatabase(): array
        {
            return $this->mockTables;
        }
    };

    expect($page->exposeFetch())
        ->toBe(['orders', 'users']);
});

it('stringifies column values for the preview modal', function () {
    $page = new class extends DatabaseBrowserPage {
        public function stringify(mixed $value): string
        {
            return $this->stringifyColumnValue($value);
        }
    };

    expect($page->stringify(null))->toBe(trans('database-browser::messages.preview.null'));
    expect($page->stringify(['foo' => 'bar']))->toContain('foo');
});

it('disables write mode when configuration turns it off', function () {
    config(['database-browser.writes.enabled' => false]);

    $page = new class extends DatabaseBrowserPage {
        public function canWrite(): bool
        {
            return $this->canModifyRecords();
        }

        public static function canAccess(): bool
        {
            return true;
        }
    };

    expect($page->canWrite())->toBeFalse();
});
