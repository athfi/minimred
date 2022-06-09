@extends('errors::minimal')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('message')
    Unauthorized
    <br>
    <div class="text-sm">
        {{($exception->getMessage()??'')}}
    </div>
@endsection
