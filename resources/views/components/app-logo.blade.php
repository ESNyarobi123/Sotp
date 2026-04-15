@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="SKY Omada" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-terra text-white shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" x2="12.01" y1="20" y2="20"/></svg>
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="SKY Omada" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-terra text-white shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" x2="12.01" y1="20" y2="20"/></svg>
        </x-slot>
    </flux:brand>
@endif
