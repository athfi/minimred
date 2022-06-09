<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Minimisation Simulation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto p-2">
            @if (session()->has('message'))
                <div class="flex justify-center mt-2 bg-green-100 rounded mb-4">
                    <div
                        class="flex flex-col justify-between w-1/2 px-4 py-2 text-green-700 bg-green-100 rounded">
                        {{ session('message') }}
                    </div>
                </div>
            @endif
            @if (session()->has('error'))
                <div class="flex justify-center mt-2 bg-red-100 rounded mb-4">
                    <div
                        class="flex flex-col justify-between w-1/2 px-4 py-2 text-red-700 bg-red-100 rounded">
                        {{ session('error') }}
                    </div>
                </div>
            @endif
            <div class="flex flex-col">
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div
                        class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                        <div
                            class="shadow overflow-hidden border-b
                            border-gray-200 sm:rounded-lg bg-white p-6">
                            @php
                                $grand_total = [];
                            @endphp
                            @foreach($result as $data)
                                <div>
                                    Simulation no: {{$loop->index + 1}}
                                </div>
                                <table>
                                    <tr>
                                        <th class="border border-indigo-400 text-xl px-4" rowspan="2">
                                            Treatment group
                                        </th>
                                        @foreach($setting['factors'] as $factor)
                                            <th class="border border-indigo-400 text-xl px-4" colspan="{{count($factor['levels']) + 1}}">
                                                {{$factor['name']}}
                                            </th>
                                        @endforeach
                                    </tr>
                                    <tr>
                                        @foreach($setting['factors'] as $factor)
                                            @foreach($factor['levels'] as $level)
                                                <th class="border border-indigo-400 text-xl px-4">
                                                    {{$level['coded_value']}}
                                                    @php
                                                        $grand_total[$level['coded_value']] = 0;
                                                    @endphp
                                                </th>
                                            @endforeach
                                                @php
                                                    $grand_total[$factor['field_name']."_subTotal"] = 0;
                                                @endphp
                                            <th class="border border-indigo-400 text-xl px-4">
                                                Total
                                            </th>
                                        @endforeach
                                    </tr>

                                    @foreach($data as $group => $mini_factor)
                                        <tr>
                                            <td class="border border-indigo-400 px-4">
                                                {{$group}}
                                            </td>
                                            @foreach($setting['factors'] as $factor)
                                                @php
                                                    $total =0;
                                                @endphp
                                                @foreach($factor['levels'] as $level)
                                                    @php
                                                    $value = $mini_factor[$factor['field_name']][$level['coded_value']];
                                                    $total += $value;
                                                    $grand_total[$level['coded_value']] += $value;
                                                    @endphp
                                                    <td class="border border-indigo-400 px-4">
                                                        {{$value}}
                                                    </td>
                                                @endforeach
                                                <td class="border border-indigo-400 px-4">
                                                    {{$total}}
                                                </td>
                                                @php
                                                    $grand_total[$factor['field_name']."_subTotal"] += $total;
                                                @endphp
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td class="border border-indigo-400 px-4">Total</td>
                                        @foreach($grand_total as $total)
                                            <td class="border border-indigo-400 px-4">
                                                {{$total}}
                                            </td>
                                        @endforeach
                                    </tr>
                                </table>
                                <br>
                                <hr>
                                <br>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
