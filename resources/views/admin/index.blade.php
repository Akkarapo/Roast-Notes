<x-admin-layout :title="__('Admin Dashboard')">
    <div class="bg-paper border-2 border-cobalt rounded-card p-6">
        {{ __('Welcome, :name', ['name' => auth()->user()->name]) }}
    </div>
</x-admin-layout>
