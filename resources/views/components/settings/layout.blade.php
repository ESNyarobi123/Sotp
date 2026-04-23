{{-- Tab Navigation --}}
<div class="mb-6 flex gap-1 rounded-xl bg-smoke/5 p-1 dark:bg-white/5">
    @php
        $tabs = [
            ['route' => 'profile.edit',    'label' => 'Profile',    'icon' => 'user-circle'],
            ['route' => 'security.edit',   'label' => 'Security',   'icon' => 'shield-check'],
            ['route' => 'appearance.edit', 'label' => 'Appearance', 'icon' => 'paint-brush'],
        ];
    @endphp
    @foreach($tabs as $tab)
        @php $active = request()->routeIs($tab['route']); @endphp
        <a href="{{ route($tab['route']) }}" wire:navigate
           class="flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition-all
               {{ $active
                   ? 'bg-white text-terra shadow-sm dark:bg-smoke-light dark:text-terra-light'
                   : 'text-smoke/60 hover:text-smoke dark:text-ivory/50 dark:hover:text-ivory' }}">
            <flux:icon name="{{ $tab['icon'] }}" class="size-4" />
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>

{{-- Content --}}
<div class="w-full max-w-5xl">
    @if (!empty($heading))
        <div class="mb-5">
            <h3 class="font-semibold text-smoke dark:text-ivory">{{ $heading }}</h3>
            @if (!empty($subheading))
                <p class="text-sm text-smoke/50 dark:text-ivory/40">{{ $subheading }}</p>
            @endif
        </div>
    @endif
    {{ $slot }}
</div>
