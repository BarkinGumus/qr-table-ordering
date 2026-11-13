@extends('layouts.app')

@section('title', __('ui.guest.invalid_title'))

@section('content')
    <div class="card">
        <h1>{{ __('ui.guest.invalid_title') }}</h1>
        <p class="muted">{{ __('ui.guest.invalid_body') }}</p>
    </div>
@endsection
