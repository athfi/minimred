<div  wire:init="loadTime">
    <x-jet-form-section submit="updateSetting">
        <x-slot name="title">
            {{ __('Time randomised') }}
        </x-slot>

        <x-slot name="description">
            Select the REDCap field that will be used to store the time
            randomised. <br><br>
            The type of field that can be used to store time randomised
            is date time field.
        </x-slot>

        <x-slot name="form">
            <div class="col-span-6 space-y-1">
                <x-jet-validation-errors/>
                @if (session()->has('message'))
                    <x-succesMessage :title="session('message')" />
                @endif
            </div>
            <div class="col-span-6 sm:col-span-4">
                <x-jet-label for="time_randomised"
                             value="Field name to store time randomised"/>
                <select
                    wire:model="time_randomised"
                    class="bg-white appearance-none border-2
                            border-gray-200 rounded py-2 px-4 w-full
                            text-gray-700 leading-tight focus:outline-none
                            focus:bg-white focus:border-purple-500"
                    id="time_randomised"
                    type="text"
                >
                    @foreach( $options as $key => $label )
                        <option
                            value={{ $key }}
                        >{{"$key ($label)" }} </option>
                    @endforeach
                </select>
                <x-jet-input-error for="time_randomised" class="mt-2"/>
            </div>

            <div class="col-span-6 space-y-1">
                <div wire:loading.block wire:target="updateSetting">
                    <x-loading :message="'Saving distance setting'"/>
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
