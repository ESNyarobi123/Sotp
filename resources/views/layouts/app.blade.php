<x-layouts::app.sidebar :title="$title ?? null">
    <div class="relative min-h-[calc(100dvh-3.5rem)]">
        {{ $slot }}
    </div>
</x-layouts::app.sidebar>
