<?php

namespace Mdmnv\FilamentDatabaseViewer\Filament\Pages;

use DateTimeInterface;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Mdmnv\FilamentDatabaseViewer\Models\DatabaseBrowserRecord;

class DatabaseBrowserPage extends Page implements HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationLabel = 'Database Browser';

    protected static ?string $title = 'Database Browser';

    protected static string|null|\UnitEnum $navigationGroup = 'Database';

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::CircleStack;

    protected static ?int $navigationSort = 9000;

    protected string $view = 'filament.pages.database-browser';

    protected const PREVIEW_LIMIT = 100;

    protected const COLUMN_PREVIEW_THRESHOLD = 70;

    protected Width | string | null $maxContentWidth = 'full';

    /**
     * @var array<int, string>
     */
    public array $availableTables = [];

    public ?string $selectedTable = null;

    /**
     * @var array<int, string>
     */
    public array $columnNames = [];

    public int $totalRows = 0;

    public ?string $columnPreviewHeading = null;

    public ?string $columnPreviewValue = null;

    /**
     * @var array<int, string>
     */
    protected array $sensitiveColumnFragments = [
        'password',
        'secret',
        'token',
        'recovery_code',
        'two_factor',
        'otp',
    ];

    /**
     * Columns that should never be altered directly.
     *
     * @var array<int, string>
     */
    protected array $immutableColumns = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->availableTables = $this->fetchAvailableTables();

        if (! $this->selectedTable && count($this->availableTables)) {
            $this->selectedTable = $this->availableTables[0];
        }

        $this->refreshTableData(initial: true);
    }

    protected function headerActions(): array
    {
        $actions = [];

        if (count($this->availableTables)) {
            $actions[] = Action::make('selectTable')
                ->label('Select Table')
                ->icon(Heroicon::TableCells)
                ->color('gray')
                ->modalHeading('Select Table')
                ->modalSubmitActionLabel('Apply')
                ->schema([
                    Select::make('table')
                        ->label('Table')
                        ->options(collect($this->availableTables)->mapWithKeys(fn (string $table): array => [$table => $table])->all())
                        ->default($this->selectedTable)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->updatedSelectedTable($data['table'] ?? null);
                });
        }

        $actions[] = Action::make('refresh')
            ->label('Refresh')
            ->icon(Heroicon::ArrowPath)
            ->color('gray')
            ->disabled(fn (): bool => blank($this->selectedTable))
            ->action(function (): void {
                $this->refreshTableData();

                Notification::make()
                    ->title('Table data refreshed')
                    ->success()
                    ->send();
            });

        if ($this->canModifyRecords() && ! empty($this->getMutableColumns())) {
            $actions[] = Action::make('create')
                ->label('Create')
                ->icon(Heroicon::PlusCircle)
                ->modalHeading(fn (): string => 'Create record')
                ->schema(fn () => $this->getDynamicFormComponents(includeImmutable: false))
                ->action(function (array $data): void {
                    if (! $this->selectedTable) {
                        $this->notifyFailure('Select a table before creating a record.');

                        return;
                    }

                    if (! $this->canModifyRecords()) {
                        $this->notifyFailure('Writes are disabled for this environment.');

                        return;
                    }

                    $payload = Arr::only($data, $this->getMutableColumns());

                    $payload = collect($payload)
                        ->map(fn ($value) => $value === '' ? null : $value)
                        ->filter(static fn ($value) => $value !== null)
                        ->all();

                    if (! count($payload)) {
                        $this->notifyFailure('Provide at least one value before creating a record.');

                        return;
                    }

                    DB::connection($this->getWriteConnectionName())
                        ->table($this->selectedTable)
                        ->insert($payload);

                    $this->refreshTableData();

                    Notification::make()
                        ->title('Record created')
                        ->success()
                        ->send();
                });
        }

        return $actions;
    }

    #[On('database-browser::refresh-table')]
    public function handleRefreshEvent(): void
    {
        $this->refreshTableData();
    }

    public function updatedSelectedTable(?string $table): void
    {
        $this->selectedTable = ($table && in_array($table, $this->availableTables, true))
            ? $table
            : ($this->availableTables[0] ?? null);

        $this->refreshTableData();
    }

    public function table(Table $table): Table
    {
        $hasIdColumn = in_array('id', $this->columnNames, true);

        return $table
            ->query(fn (): ?EloquentBuilder => $this->getSelectedTableQuery())
            ->columns($this->getDynamicColumns())
            ->paginated()
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([25, 50, 100])
            ->searchable()
            ->striped()
            ->defaultKeySort(fn () => $hasIdColumn)
            ->headerActions($this->headerActions(), Tables\Actions\HeaderActionsPosition::Adaptive)
            ->toolbarActions([
                Action::make('tableName')
                    ->label(fn (): string => $this->selectedTable ? "Table: {$this->selectedTable}" : 'No table selected')
                    ->disabled()
                    ->color('gray')
                    ->icon(Heroicon::OutlinedCursorArrowRays)
                    ->outlined()
            ])
            ->recordActions($this->getRecordActions());
    }

    public function getTableRecordKey(Model|array $record): string
    {
        if (is_array($record)) {
            return (string) ($record['__key'] ?? md5(json_encode($record)));
        }

        $key = $record->getKey();

        if ($key !== null) {
            return (string) $key;
        }

        if ($record instanceof \App\Models\DatabaseBrowserRecord) {
            foreach ($this->columnNames as $column) {
                $value = $record->getAttribute($column);

                if (is_scalar($value) && $value !== null) {
                    return (string) $value;
                }
            }

            return spl_object_hash($record);
        }

        return spl_object_hash($record);
    }

    protected function getSelectedTableQuery(): ?EloquentBuilder
    {
        if (! $this->selectedTable) {
            return null;
        }

        return DatabaseBrowserRecord::queryForTable(
            table: $this->selectedTable,
            connection: $this->getReadConnectionName(),
        )->limit(static::PREVIEW_LIMIT);
    }

    /**
     * @return array<int, TextColumn>
     */
    protected function getDynamicColumns(): array
    {
        if (! $this->columnNames) {
            return [];
        }

        return array_map(
            fn (string $column): TextColumn => TextColumn::make($column)
                ->label(Str::headline($column))
                ->wrap()
                ->searchable()
                ->sortable()
                ->formatStateUsing(fn ($state) => $this->truncateColumnState($state))
                ->action(function ($state) use ($column) {
                    if (! $this->shouldPreviewColumn($state)) {
                        return;
                    }

                    $this->showColumnPreview($column, $state);
                })
                ->disabledClick(fn ($state): bool => ! $this->shouldPreviewColumn($state))
                ->extraCellAttributes(fn ($state): array => $this->getColumnCellAttributes($state)),
            $this->columnNames,
        );
    }

    protected function getRecordActions(): array
    {
        if (! $this->canModifyRecords()) {
            return [];
        }

        if (! in_array('id', $this->columnNames, true)) {
            return [];
        }

        return [
            Action::make('edit')
                ->label('Edit')
                ->icon(Heroicon::PencilSquare)
                ->schema(fn () => $this->getDynamicFormComponents())
                ->fillForm(fn (DatabaseBrowserRecord $record) => $this->getEditFormData($record))
                ->action(function (array $data, DatabaseBrowserRecord $record): void {
                    if (! $this->selectedTable) {
                        $this->notifyFailure('Select a table before editing.');

                        return;
                    }

                    $payload = Arr::only($data, $this->getMutableColumns());

                    $payload = collect($payload)
                        ->map(fn ($value) => $value === '' ? null : $value)
                        ->all();

                    if (! count($payload)) {
                        $this->notifyFailure('No editable data provided.');

                        return;
                    }

                    $primaryKey = $record->getKeyName();
                    $identifier = $record->getAttribute($primaryKey);

                    if ($identifier === null) {
                        $this->notifyFailure('Unable to determine the record key.');

                        return;
                    }

                    DB::connection($this->getWriteConnectionName())
                        ->table($this->selectedTable)
                        ->where($primaryKey, $identifier)
                        ->update($payload);

                    $this->refreshTableData();

                    Notification::make()
                        ->title('Record updated successfully')
                        ->success()
                        ->send();
                }),
            Action::make('delete')
                ->label('Delete')
                ->icon(Heroicon::Trash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete record?')
                ->modalDescription('This action cannot be undone.')
                ->action(function (DatabaseBrowserRecord $record): void {
                    if (! $this->selectedTable) {
                        $this->notifyFailure('Select a table before deleting records.');

                        return;
                    }

                    if (! $this->canModifyRecords()) {
                        $this->notifyFailure('Writes are disabled for this environment.');

                        return;
                    }

                    $primaryKey = $record->getKeyName();
                    $identifier = $record->getAttribute($primaryKey);

                    if ($identifier === null) {
                        $this->notifyFailure('Unable to determine the record key.');

                        return;
                    }

                    DB::connection($this->getWriteConnectionName())
                        ->table($this->selectedTable)
                        ->where($primaryKey, $identifier)
                        ->delete();

                    $this->refreshTableData();

                    Notification::make()
                        ->title('Record deleted')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getEditFormData(mixed $record): array
    {
        if ($record instanceof DatabaseBrowserRecord) {
            $recordData = $record->attributesToArray();
        } elseif (is_array($record)) {
            $recordData = $record;
        } else {
            $recordData = (array) $record;
        }

        return Arr::only($recordData, $this->columnNames);
    }

    protected function getDynamicFormComponents(bool $includeImmutable = true): array
    {
        $columns = $includeImmutable
            ? $this->columnNames
            : $this->getMutableColumns();

        if (! $columns) {
            return [];
        }

        $components = array_map(function (string $column) {
            $field = TextInput::make($column)
                ->label(Str::headline($column))
                ->columnSpan(1);

            if (in_array($column, $this->immutableColumns, true)) {
                $field->disabled();
            }

            return $field;
        }, $columns);

        $columns = min(max((int) ceil(count($components) / 8), 1), 3);

        return [
            Grid::make()
                ->schema($components)
                ->columns($columns),
        ];
    }

    protected function canModifyRecords(): bool
    {
        if (! static::canAccess()) {
            return false;
        }

        $allowWrites = true; // Agar edit qilishni o'chirish kerak bo'lsa `false` qilinsin

        return filter_var($allowWrites, FILTER_VALIDATE_BOOLEAN);
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return array<int, string>
     */
    protected function fetchAvailableTables(): array
    {
        return collect(
            DB::connection($this->getReadConnectionName())
                ->select('SELECT tablename FROM pg_tables WHERE schemaname = ?', ['public'])
        )
            ->pluck('tablename')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    protected function refreshTableData(bool $initial = false): void
    {
        if ($this->selectedTable) {
            $this->columnNames = $this->resolveColumnNames($this->selectedTable);
            $this->totalRows = $this->getReadConnection()
                ->table($this->selectedTable)
                ->count();
        } else {
            $this->columnNames = [];
            $this->totalRows = 0;
        }

        if (! $initial) {
            $this->resetTable();
        }
    }

    /**
     * @return array<int, string>
     */
    protected function resolveColumnNames(string $table): array
    {
        $firstRow = $this->getReadConnection()
            ->table($table)
            ->limit(1)
            ->first();

        if ($firstRow) {
            return $this->sanitizeColumnList(array_keys((array) $firstRow));
        }

        $columns = collect(
            $this->getReadConnection()
                ->select(
                    'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ? ORDER BY ordinal_position',
                    ['public', $table]
                )
        )
            ->pluck('column_name')
            ->all();

        return $this->sanitizeColumnList($columns);
    }

    protected function getReadConnectionName(): string
    {
        return (string) config('database.default', 'pgsql');
    }

    protected function getWriteConnectionName(): string
    {
        return (string) config('database.default', 'pgsql');
    }

    protected function getReadConnection(): ConnectionInterface
    {
        return DB::connection($this->getReadConnectionName());
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<int, string>
     */
    protected function sanitizeColumnList(array $columns): array
    {
        return collect($columns)
            ->map(fn (string $column) => trim($column))
            ->filter()
            ->filter(function (string $column): bool {
                $needle = Str::lower($column);

                foreach ($this->sensitiveColumnFragments as $fragment) {
                    if (Str::contains($needle, $fragment)) {
                        return false;
                    }
                }

                return true;
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function getMutableColumns(): array
    {
        return collect($this->columnNames)
            ->reject(fn (string $column): bool => in_array($column, $this->immutableColumns, true))
            ->values()
            ->all();
    }

    public function getColumnPreviewModalId(): string
    {
        return 'database-browser-column-preview';
    }

    public function showColumnPreview(string $column, mixed $value): void
    {
        $this->columnPreviewHeading = Str::headline($column);
        $this->columnPreviewValue = $this->stringifyColumnValue($value);

        $this->dispatch('open-modal', id: $this->getColumnPreviewModalId());
    }

    protected function stringifyColumnValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s.uP');
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value) || is_object($value)) {
            $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($encoded !== false) {
                return $encoded;
            }
        }

        return (string) Str::of(print_r($value, true));
    }

    protected function shouldPreviewColumn(mixed $state): bool
    {
        if (! is_string($state)) {
            return false;
        }

        return Str::length($state) > static::COLUMN_PREVIEW_THRESHOLD;
    }

    protected function truncateColumnState(mixed $state): mixed
    {
        if (! is_string($state)) {
            return $state;
        }

        if (! $this->shouldPreviewColumn($state)) {
            return $state;
        }

        return Str::limit($state, static::COLUMN_PREVIEW_THRESHOLD, '…');
    }

    protected function getColumnCellAttributes(mixed $state): array
    {
        if (! $this->shouldPreviewColumn($state)) {
            return [];
        }

        return [
            'class' => 'whitespace-pre-wrap break-words cursor-pointer text-sm max-w-[28rem]',
            'title' => $state,
        ];
    }

    protected function notifyFailure(string $message): void
    {
        Notification::make()
            ->title($message)
            ->danger()
            ->send();
    }
}
