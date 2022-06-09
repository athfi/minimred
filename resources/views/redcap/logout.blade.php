<x-guest-layout>
    <x-jet-authentication-card>
        <x-slot name="logo">
            <x-jet-authentication-card-logo />
            <div class="text-center text-5xl text-indigo-700">
                Minim<span class="text-red-700">Red</span>
                <h3 class="text-xl">
                    Minimisation for <span class="text-red-700">REDCap</span>
                </h3>
            </div>
        </x-slot>

        <div class="mb-4 font-medium text-2xl text-green-600 text-center">
            Thank you {{$name??""}} for using the minimRed application.

        </div>
        @if ($message)
            <div class="mb-4 font-medium text-xl text-indigo-800 text-green-700 text-center">
                {{$message}}
            </div>
        @endif

    </x-jet-authentication-card>
</x-guest-layout>
