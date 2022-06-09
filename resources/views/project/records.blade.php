<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Records
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto p-2">

            <div class="mt-10 sm:mt-0">
                <x-jet-action-section>
                    <x-slot name="title">
                        {{ __('Records') }}
                    </x-slot>

                    <x-slot name="description">
                        {{ __('All of the records from REDCap') }}
                    </x-slot>

                    <!-- Team Member List -->
                    <x-slot name="content">
                        <div class="space-y-6">
                            <div class="items-center justify-between overflow-x-auto">
                                @livewire('project.records-show')
                            </div>
                        </div>
                    </x-slot>
                </x-jet-action-section>
            </div>
        </div>

</x-app-layout>
