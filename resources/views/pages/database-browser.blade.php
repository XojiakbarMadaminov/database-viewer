<x-filament-panels::page>
    <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        {{ $this->table }}
    </div>

    <x-filament::modal
            :id="$this->getColumnPreviewModalId()"
            :heading="$this->columnPreviewHeading ?? 'Column value'"
            width="2xl"
    >
        <div class="space-y-4 text-sm">
            <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                {{ $this->columnPreviewHeading ?? 'Column value' }}
            </p>

            <div
                    class="max-h-[60vh] overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-4 font-mono text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 whitespace-pre-wrap break-words"
            >
                {{ $this->columnPreviewValue ?? '—' }}
            </div>
        </div>
    </x-filament::modal>
</x-filament-panels::page>
