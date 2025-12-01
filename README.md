# Database Browser for Filament

A reusable Filament page that surfaces PostgreSQL tables, lets you inspect, search, sort, and optionally edit or delete rows from a configurable read/write connection. All strings, views, and actions are configurable/publishable so you can tailor the browser to any panel.

## Installation

1. **Require the package**

   ```bash
   composer require mdmnv/filament-database-viewer
   ```

2. **Register the service provider** (Laravel auto-discovers packages, but you can register it manually if discovery is disabled.)

   ```php
   'providers' => [
       // ...
       Mdmnv\FilamentDatabaseViewer\DatabaseBrowserServiceProvider::class,
   ],
   ```

3. **Publish the assets**

   ```bash
   php artisan vendor:publish --tag=database-browser-config
   php artisan vendor:publish --tag=database-browser-views
   php artisan vendor:publish --tag=database-browser-translations
   ```

4. **Register the page with your Filament panel**

   ```php
   use Mdmnv\FilamentDatabaseViewer\Filament\Pages\DatabaseBrowserPage;

   public function panel(Panel $panel): Panel
   {
       return $panel
           // ...
           ->pages([
               DatabaseBrowserPage::class,
           ]);
   }
   ```

5. **Configure authorization and limits**

   The published config (`config/database-browser.php`) controls:

   - Read/write connection names
   - Allowed/disallowed tables + listing limit
   - Preview limit + column truncation threshold
   - Toggle for write actions
   - Authorization via a Gate ability or invokable policy class
   - Navigation label/group/icon/sort
   - Livewire namespace for multi-panel safety

## Testing

Run Pest (or PHPUnit) once dependencies are installed:

```bash
./vendor/bin/pest
```

The suite covers table listing filters, modal preview formatting, and ensuring write mode stays disabled when configured.
