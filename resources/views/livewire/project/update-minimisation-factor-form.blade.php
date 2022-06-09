@php
    $radioOption = json_decode($json_radio_fields, true);
@endphp


<div wire:init="loadRadio">
    <x-jet-form-section submit="updateSetting">
        <x-slot name="title">
            {{ __('Prognostic factors') }}
        </x-slot>

        <x-slot name="description">
            Setup redcap field that will be usec as prognostic factor(s).
            At least one factor is required for minimisation.
            Weight indicate the relative importance of factor for minimisation

        </x-slot>

        <x-slot name="form">
            <div class="col-span-6 space-y-1">
                <x-jet-validation-errors/>
                @if (session()->has('message'))
                    <x-succesMessage :title="session('message')"/>
                @endif
            </div>
            <div wire:loading wire:target="cancel"
                 class="col-span-6 space-y-1">
                <x-loading :message="'Deleting factor...'"/>
            </div>


            <div class="col-span-6 space-y-1">
                <table class="text-center divide-y divide-gray-300 w-full">
                    <!-- Table header -->
                    <thead class="justify-between">
                    <tr class="bg-gray-800">

                        <th class="px-2 py-2 min-w-full">
                            <span class="text-white">Factor</span>
                        </th>
                        <th class="px-2 py-2 min-w-full">
                            <span class="text-white">Levels</span>
                        </th>
                        <th class="px-2 py-2 w-20">
                            <span class="text-white">Weight</span>
                        </th>
                        <th class="px-2 py-2 w-20">
                            <span class="text-white">Action</span>
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-gray-50">
                    @foreach ( $factors as $key => $factor )
                        <!-- content  -->
                        <tr class="bg-white border-2 border-gray-300 hover:bg-gray-100">
                            <td class="text-left">
                                <div
                                    class="ml-2 font-semibold">{{$factor['name']}}</div>
                            </td>
                            <td>
                                |
                                @foreach($factor['levels'] as $level )
                                    {{$level['label'] . " (" . $level['coded_value'].") |"}}
                                @endforeach
                            </td>
                            <td class="px-2 py-2">
                                <x-jet-input
                                    id="token" type="number"
                                    class="mt-1 block w-20"
                                    min="1.0" step="0.1"
                                    :wire:key="$loop->index"
                                    wire:model.lazy="factors.{{ $key }}.weight"
                                    value="{{$factor['weight']}}"
                                />
                                <x-jet-input-error class="text-left"
                                                   for="factors.{{ $key }}.weight"
                                                   class="mt-2"/>
                            </td>
                            <td class="w-20">
                                <button
                                    cla
                                    ss="text-red-500 inline-block ml-1 cursor-pointer"
                                    title="Delete &quot;{{$factor['name']}}&quot;">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         class="h-6 w-6" fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor"
                                         :wire:key="$factor['field_name']"
                                         wire:click.prevent="destroy('{{ $factor['field_name'] }}')"
                                    >
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="border-2 border-gray-300">
                        <td colspan="3">
                            @if($radioOption)
                                <select
                                    wire:model="new_factor"
                                    class="bg-white appearance-none border-2
                                        border-gray-200 rounded py-2 px-4 w-full
                                        text-gray-700 leading-tight focus:outline-none
                                        focus:bg-white focus:border-purple-500"
                                    id="inline-full-name"
                                    type="text"
                                >
                                    @foreach($radioOption as $redcapFieldName => $meta)
                                        @php
                                            $ln = 30;
                                            $label = is_string($meta) ? $meta : $meta['label'] ;
                                            $label = strip_tags($label);
                                            if ( strlen($label) > $ln )
                                            {
                                              $label = substr( $label, 0, $ln ) . "... )";
                                            }
                                        @endphp
                                        <option
                                            value={{ $redcapFieldName }}
                                        >{{ $redcapFieldName . " ($label)"}} </option>
                                    @endforeach
                                </select>
                            @else
                                <x-jet-input
                                    id="new_factor" type="text"
                                    class="mt-1 block min-w-full"
                                    wire:model.lazy="new_factor"
                                />
                            @endif

                            <x-jet-input-error for="new_factor" class="mt-2"/>

                        </td>
                        <td>
                            <button wire:click.prevent="add"
                                    class="inline-flex items-center px-4 py-2 bg-gray-800
                                        border border-transparent rounded-md font-semibold
                                        text-xs text-white uppercase tracking-widest
                                        hover:bg-gray-700 active:bg-gray-900 focus:outline-none
                                        focus:border-gray-900 focus:ring focus:ring-gray-300
                                        disabled:opacity-25 transition">
                                ADD
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>


            <div class="col-span-6 space-y-1">
                <div wire:loading.block wire:target="updateGroup">
                    <x-loading :message="'Saving group allocation setting'"/>
                </div>
            </div>
        </x-slot>

        <x-slot name="actions" class="space-y-6">
            <button wire:click.prevent="cancel"
                    class="inline-flex items-center px-4 py-2 bg-gray-800
                    border border-transparent rounded-md font-semibold
                    text-xs text-white uppercase tracking-widest
                    hover:bg-gray-700 active:bg-gray-900 focus:outline-none
                    focus:border-gray-900 focus:ring focus:ring-gray-300
                    disabled:opacity-25 transition">
                CANCEL
            </button>
            <x-jet-button class="ml-4">
                {{ __('Save') }}
            </x-jet-button>
        </x-slot>

    </x-jet-form-section>
</div>
