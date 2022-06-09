<div>
    <x-jet-form-section submit="generate">
        <x-slot name="title">
            {{ __('REDCap bookmarks') }}
        </x-slot>

        <x-slot name="description">
            {{ __('Generate REDCap project bookmarks that allow REDCap user to login to this minimisation site.') }}
        </x-slot>

        <x-slot name="form">
            <div class="col-span-6 space-y-1">
                <x-jet-validation-errors/>
                @if (session()->has('message'))
                    <x-succesMessage :title="session('message')" />
                @endif
            </div>

            <div class="col-span-6 sm:col-span-6">
                <x-jet-label for="bookmard_records" value="REDCap bookmarks for records"/>
                <textarea
                    disabled
                    wire:model.debounce.200ms="bookmard_records"
                    class="w-full"
                    name="bookmard_records" id="bookmard_records" rows="3">
                </textarea>
            </div>

            <div class="col-span-6 sm:col-span-6">
                <x-jet-label for="bookmard_randomise" value="REDCap bookmarks for randomisation"/>
                <textarea
                    disabled
                    wire:model.debounce.200ms="bookmard_randomise"
                    class="w-full"
                    name="bookmard_randomise" id="bookmard_randomise" rows="3">
                </textarea>
            </div>

        </x-slot>

        <x-slot name="actions">
            <x-jet-button>
                {{ __('Generate') }}
            </x-jet-button>
        </x-slot>


    </x-jet-form-section>
</div>
