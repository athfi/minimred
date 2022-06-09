
<div wire:init="startLoad">
    @if (session()->has('error'))
        <div
            x-data="{show:true}"
            x-show="show"
            class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"
            role="alert">
            <strong class="font-bold">Error!</strong>
            <span
                class="block sm:inline">{{ session('error') }}</span>
            <span
                class="absolute top-0 bottom-0 right-0 px-4 py-3">
                                <svg class="fill-current h-6 w-6 text-red-500" role="button"
                                     @click="show=false"
                                     xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 20 20"><title>Close</title><path
                                        d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                            </span>
        </div>
    @endif
    <table class="border-collapse border border-indigo-600 items-start align-baseline mx-auto">
        <tr>
            <td class="border border-indigo-400 text-xl px-4">Participant ID</td>
            <td class="border border-indigo-400 text-xl px-4">Group</td>
            @if($field['randTime']??'')
                <td class="border border-indigo-400 text-xl px-4">Randomisation time</td>
            @endif
            <td class="border border-indigo-400 text-xl px-4">Action</td>
        </tr>
        @forelse($records as $record)
            <tr>
                <td class="border border-indigo-400 px-4">
                    <a href=" {{route('project.record', ['record_id' => $record[$field['recordId']] ])}}">
                        {{$record[$field['recordId']]}}
                    </a>
                </td>
                <td class="border border-indigo-400 px-4">
                    @if($field['randGroup']??'')
                        {{$record[$field['randGroup']]}}
                    @endif
                </td>
                @if($field['randTime']??'')
                <td class="border border-indigo-400 px-4">
                    {{$record[$field['randTime']]}}
                </td>
                @endif
                <td class="border border-indigo-400 px-4 text-center">
                    @if($field['randGroup'] && $record[$field['randGroup']]=='')
                        <a class="text-green-500 hover:text-red-500"
                           href="{{route('project.randomise', ['record_id' => $record[ $field[ 'recordId'] ] ] ) }}">
                            randomise
                        </a>
                    @else
                        <a class= "text-green-500 hover:text-red-500"
                           href= "{{route('project.record', ['record_id' => $record[$field['recordId']] ])}}">
                            view
                        </a>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td class="border border-indigo-400 px-4" colspan="4">
                    <div wire:loading>
                        <x-loading :message="'Loading records from REDcap..'"/>
                    </div>
                    <div wire:loading.remove>
                        No records found
                    </div>

                </td>
            </tr>
        @endforelse
    </table>
    {{ $records->links() }}
</div>
