<x-redcap-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-white leading-tight">
            Project : {{ __($project->name) }}
        </h2>
        <h3 class="text-sm text-white">
            {{session('redcap')['redcap_info']['project_title']}}
        </h3>
    </x-slot>
@php
    $record = collect( $record );
@endphp
    <div class="py-12">
        <div class="max-w-7xl mx-auto p-2">
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-jet-section-title>
                    <x-slot name="title">
                        @if($action == 'randomise')
                            Randomisation page
                        @else
                            Record
                        @endif
                    </x-slot>
                    <x-slot name="description">
                        @if($action == 'randomise')
                            Randomisation page show prognostic factor(s) of Participant.
                            Please review the participant data before randomise the participant.
                        @else
                            Participant data from REDCap.
                            <br>
                            Only fields related to minimisation are shown here.
                        @endif

                    </x-slot>
                </x-jet-section-title>
                <div class="mt-5 md:mt-0 md:col-span-2">
                    <div
                        class="px-4 py-5 bg-white sm:p-6 shadow sm:rounded-tl-md sm:rounded-tr-md ">
                        @if (session()->has('message'))
                            <div class="col-span-6 space-y-1 mb-4">
                                <x-succesMessage :title="session('message')"/>
                            </div>
                        @endif
                        <div class="grid grid-cols-6 gap-6">
                            @if($record)
                                @foreach($fields as $key => $field)
                                    @if( $field !== '' &&
                                            $record->has($field) )
                                        <div class="col-span-6 sm:col-span-5 ">
                                            @if( $key == 'randGroup' )
                                                <div class="hidden sm:block">
                                                    <div class="py-2">
                                                        <div
                                                            class="border-t border-b border-gray-200">
                                                            <span
                                                                class="text-xl">Group to which participant has been allocated</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="">
                                                <x-jet-label
                                                    value="{{ strip_tags($metadata[$field]['field_label']??$field) }}"/>
                                            </div>
                                            <div class="ml-3">
                                                @if($metadata->has($field) && $record->has($field) )
                                                    <div class="p-2 border-gray-600 bg-gray-50
                                                            rounded-md shadow-sm overflow-y-auto ">
                                                        @if($metadata[$field]['field_type'] == 'checkbox')
                                                            @foreach($metadata[$field]['select_choices_or_calculations'] as $key => $label )
                                                                <div>
                                                                    <input
                                                                        class="disabled:opacity-50 text-gray-400 cursor-not-allowed"
                                                                        type="checkbox"
                                                                        id="{{$field."___".$key}}"
                                                                        name="{{$field."___".$key}}"
                                                                        value="{{$key}}"
                                                                        {{$record[$field][$key]?'checked':''}} disabled>
                                                                    {{$metadata[$field]['select_choices_or_calculations'][$key]}}
                                                                </div>
                                                            @endforeach
                                                        @elseif($metadata[$field]['field_type'] == 'radio')
                                                            @foreach($metadata[$field]['select_choices_or_calculations'] as $key => $label )
                                                                <div>
                                                                    <input
                                                                        class="disabled:opacity-50 text-gray-400 cursor-not-allowed"
                                                                        type="radio"
                                                                        id="{{$key}}"
                                                                        name="{{$field}}"
                                                                        value="{{$record[$field]}}"
                                                                        {{$key==$record[$field]?'checked':''}}
                                                                        disabled>
                                                                    {{$metadata[$field]['select_choices_or_calculations'][$key]}}
                                                                </div>
                                                            @endforeach
                                                        @elseif($metadata[$field]['field_type'] == 'yesno')
                                                            <div>
                                                                <input
                                                                    class="disabled:opacity-50 text-gray-400"
                                                                    type="radio"
                                                                    id="{{$field."__yes"}}"
                                                                    name="{{$field}}"
                                                                    value="1"
                                                                    {{$record[$field]==1?'checked':''}}
                                                                    disabled>
                                                                Yes
                                                            </div>
                                                            <div>
                                                                <input
                                                                    class="disabled:opacity-50 text-gray-400"
                                                                    type="radio"
                                                                    id="{{$field."__no"}}"
                                                                    name="{{$field}}"
                                                                    value="0"
                                                                    {{$record[$field]==0?'checked':''}}
                                                                    disabled>
                                                                No
                                                            </div>
                                                        @else
                                                            <x-jet-label
                                                                value="{{$record[$field]==''?'- ':$record[$field]}}"
                                                                class="p-2 border-gray-600 bg-gray-50 rounded-md shadow-sm overflow-y-auto"/>
                                                        @endif
                                                    </div>
                                                @else
                                                    <x-jet-label
                                                        value="Field '{{$field}}' could not be found in the REDCap metadata."
                                                        class="p-2 focus:order-gray-600 bg-gray-50 rounded-md
                                                        shadow-sm overflow-y-auto text-sm text-red-600"/>
                                                @endif
                                            </div>
                                            @if($minim_errors[$field]??"")
                                                    @if( $field != (isset($fields[ 'randGroup' ])?$fields[ 'randGroup' ]:"") || $action == 'randomise' )
                                                    @foreach( $minim_errors[$field] as $key => $message)
                                                        <div
                                                            class="text-sm text-red-600 ml-3 mt-3 ">
                                                            {{$message}}
                                                        </div>
                                                    @endforeach
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            @else
                                <div
                                    class="md:flex md:items-center mb-6 md:flex-1 w-full">
                                    Sorry, we cannot find a record with
                                    id {{request('id')}}
                                </div>
                            @endif
                        </div>
                    </div>
                    <div
                        class="flex items-center justify-end px-4 py-3 bg-gray-50
                                text-right sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md"
                    >
                        <a class="ml-4 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition"
                           href=" {{route('redcap.records')}}">
                            {{ __('Back') }}
                        </a>
                        @if($record)
                            @if($action =='randomise' )
                                @if($minim_errors)
                                    <x-jet-button
                                        disabled
                                        class="ml-4 cursor-not-allowed"
                                    >
                                        {{ __('Randomise') }}
                                    </x-jet-button>
                                @else
                                    <form method="POST"
                                          action="{{route('redcap.minimise', ['record_id' => $record['record_id'] ])}}">
                                        @csrf
                                        <x-jet-button class="ml-4">
                                            {{ __('Randomise') }}
                                        </x-jet-button>
                                    </form>
                                @endif
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
</x-redcap-layout>
