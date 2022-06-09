<x-redcap-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-white leading-tight">
            Project : {{ __($project->name) }}
        </h2>
        <h3 class="text-sm text-white">
            {{session('redcap')['redcap_info']['project_title']}}
        </h3>
    </x-slot>
    <div class="bg-green-200 text-center">
        @if(session('message'))
            {{session('message')}}
        @endif
    </div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto p-2">
            <div class="mt-10 sm:mt-0">
                <x-jet-action-section>
                    <x-slot name="title">
                        {{ __('Records') }}
                    </x-slot>

                    <x-slot name="description">
                        {{ __('Records from REDCap') }}
                    </x-slot>

                    <!-- Team Member List -->
                    <x-slot name="content">
                        <div class="space-y-6">
                            <div class="items-center justify-between overflow-x-auto">
                                @livewire('redcap.records-show',['project' => $project ])
                            </div>
                        </div>
                    </x-slot>
                </x-jet-action-section>
            </div>
        </div>

</x-redcap-layout>
