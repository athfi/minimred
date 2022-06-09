<div >
    <x-jet-form-section submit="updateSetting">
        <x-slot name="title">
            {{ __('Probability') }}
        </x-slot>

        <x-slot name="description">
        </x-slot>

        <x-slot name="form">
            <div class="col-span-6 space-y-1">
                <x-jet-validation-errors/>
                @if (session()->has('message'))
                    <x-succesMessage :title="session('message')" />
                @endif
            </div>

            <div class="col-span-6 sm:col-span-4">
                <x-jet-label for="prob_method" value="Probability method"/>
                <select
                    wire:model="prob_method"
                    class="bg-white appearance-none border-2
                            border-gray-200 rounded py-2 px-4 w-full
                            text-gray-700 leading-tight focus:outline-none
                            focus:bg-white focus:border-purple-500"
                    id="prob_method"
                    type="text"
                >
                    @foreach( $list_prob as $key => $label )
                        <option
                            value={{ $key }}
                        >{{ $label }} </option>
                    @endforeach
                </select>
                <x-jet-input-error for="name" class="mt-2"/>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <x-jet-label for="base_prob" value="{{$prob_label}}"/>
                <x-jet-input id="base_prob" type="number" min="1" max="99" class="mt-1 block w-full"
                             wire:model.debounce.200ms="base_prob"/>
                <x-jet-input-error for="url" class="mt-2"/>
            </div>

            <div class="col-span-6 space-y-1">
                <div wire:loading.block wire:target="updateProb">
                    <x-loading :message="'Saving probability setting'"/>
                </div>
            </div>
        </x-slot>

        <x-slot name="actions">
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
