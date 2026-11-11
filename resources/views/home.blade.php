@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>{{ config('app.name') }}</h1>
        <p class="muted">{{ __('ui.app.tagline') }}</p>
        <a class="btn" href="{{ route('login') }}">{{ __('ui.app.staff_login') }}</a>
    </div>
@endsection
