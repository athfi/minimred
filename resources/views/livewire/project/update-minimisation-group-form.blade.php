@php
    $radioOption = json_decode($json_radio_fields, true);
    ksort($radioOption);
@endphp


<div wire:init="loadRadio">
    <x-jet-form-section submit="updateSetting">
        <x-slot name="title">
            {{ __('Group allocation') }}
        </x-slot>

        <x-slot name="description">
            Select the REDCap field that will be used to store the randomisation
            results and specify the allocation ratio.
            <br><br>
            The type of field that can be used to store randomisation
            results is multiple choice with single option (radio).
        </x-slot>

        <x-slot name="form">
            <div class="col-span-6 space-y-1">
                <x-jet-validation-errors/>
                @if (session()->has('message'))
                    <x-succesMessage :title="session('message')" />
                @endif
            </div>

            <div class="col-span-6 sm:col-span-4">
                <x-jet-label for="name" value="{{ __('Field to store randomisation') }}"/>
                @if($radioOption)
                <select
                    wire:model="field_name"
                    class="bg-white appearance-none border-2
                            border-gray-200 rounded py-2 px-4 w-full
                            text-gray-700 leading-tight focus:outline-none
                            focus:bg-white focus:border-purple-500"
                    id="inline-full-name"
                    type="text"
                    autofocus
                >
                    <option value="" disabled>Please select ...</option>
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
                        id="field_name" type="text"
                        class="mt-1 block min-w-full"
                        wire:model.lazy="field_name"
                    />
                @endif

                <x-jet-input-error for="name" class="mt-2"/>
                <div wire:loading wire:target="cancel">
                    <x-loading :message="'Loading last saved setting...'"/>
                </div>
                <div wire:loading wire:target="field_name">
                    <x-loading :message="'Loading groups...'"/>
                </div>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <table class="text-center w-full">
                    <!-- Table header -->
                    <thead class="justify-between">
                    <tr class="bg-gray-800">

                        <th class="px-2 py-2">
                            <span class="text-white">Name</span>
                        </th>
                        <th class="px-2 py-2 w-16 ">
                            <span class="text-white">Ratio</span>
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-gray-50">
                    @forelse( $groups as $group )
                        <!-- content  -->
                        <tr class="bg-white border-2 border-gray-300 hover:bg-gray-100">
                            <td class="text-left">
                            <div
                                class="ml-2 font-semibold">{{$group['name']}}</div>
                            </td>
                            <td class="px-2 py-2">
                                <x-jet-input
                                    id="token" type="number"
                                    class="mt-1 block w-20"
                                    min="1" step="1"
                                    :wire:key="$loop->index"
                                    wire:model.lazy="groups.{{ $loop->index }}.ratio"
                                />
                                <x-jet-input-error class="text-left" for="groups.{{ $loop->index }}.ratio" class="mt-2"/>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="border-2 border-gray-300 bg-gray-50" colspan="3">
                                    No field selected
                            </td>
                        </tr>
                    @endforelse
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
