@extends('errors.layout')

@section('title', 'Page not found')
@section('code', '404')
@section('heading', 'Page not found')
@section('message', 'The page you requested does not exist or may have been moved.')

@section('icon')
    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M12 18h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
@endsection
