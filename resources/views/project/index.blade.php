<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ __('My Projects') }}
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
                        {!! session('error') !!}
                    </div>
                </div>
            @endif
            <div class="flex flex-col">
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div
                        class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                        <div
                            class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg bg-white">
                            <table
                                class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-fhosting-blue-200">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 w-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">
                                        No
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">
                                        Project name
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                                </thead>
                                <tbody
                                    class="bg-white divide-y divide-gray-200">


                                @forelse($projects as $project)
                                    <tr class="hover:bg-gray-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div
                                                class="text-sm font-medium text-gray-900">{{$loop->index + 1}}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a class="text-blue-600 hover:blue-900 hover:underline"
                                               href="{{route('project.switch',['project'=> $project->id])}}">
                                                {{$project->name}}
                                            </a>
                                            @if(Auth::user()->isCurrentProject($project))
                                                <span class="text-green-600">
                                                    ( current project )
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">

                                            <div
                                                class="text-sm font-medium text-gray-900">
                                                @if(Auth::user()->isCurrentProject($project))
                                                    <span class="text-gray-500">
                                                    switch
                                                    </span>
                                                @else
                                                    <a class="text-blue-600 hover:blue-900 hover:underline"
                                                       href="{{route('project.switch',['project'=> $project->id])}}">
                                                        switch
                                                    </a>
                                                @endif

                                                <form method="POST"
                                                      action="{{route("project.destroy",['project'=>$project->id])}}"
                                                      class="inline-block ml-1">
                                                    {{ csrf_field() }}
                                                    {{ method_field('DELETE') }}
                                                    <button
                                                        class="text-red-500 inline-block ml-1 cursor-pointer"
                                                        type="submit">
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="h-6 w-6"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="px-6 py-4 whitespace-nowrap text-center">
                                            No projects found. Please create a
                                            new project to setup new
                                            minimization.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                            <hr>
                            <div
                                class="text-center px-4 py-3 bg-fhosting-blue-50">
                                <a href="{{URL::route('projects.create');}}"
                                   class="text-indigo-600 hover:text-indigo-900 hover:underline">Create
                                    new project</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
