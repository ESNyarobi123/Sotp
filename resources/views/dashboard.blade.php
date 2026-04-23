<x-layouts::app :title="__('Dashboard')">
    @if(auth()->user()->isAdmin())
        <livewire:admin.dashboard />
    @else
        <livewire:customer.dashboard />
    @endif
</x-layouts::app>
