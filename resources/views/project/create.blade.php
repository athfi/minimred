<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Project') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto p-2">
            @if (session()->has('message'))
                <div class="flex justify-center mt-2 bg-green-100 rounded mb-4">
                    <div
                        class="flex flex-col justify-between w-1/2 px-4 py-2 text-green-700 bg-green-100 rounded">
                        {{ session('message') }}
                    </div>
                </div>
            @endif
            @livewire('project.create')

        </div>
    </div>
</x-app-layout>
