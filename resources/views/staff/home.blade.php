@extends('layouts.app')

@section('title', __('ui.staff.title'))

@section('content')
    <div class="card">
        <h1>{{ __('ui.staff.welcome', ['name' => auth()->user()->name]) }}</h1>

        <dl>
            <dt>{{ __('ui.staff.restaurant') }}</dt>
            <dd>{{ auth()->user()->restaurant->name }}</dd>
            <dt>{{ __('ui.staff.role') }}</dt>
            <dd>{{ __('ui.roles.'.auth()->user()->role->value) }}</dd>
        </dl>

        <p class="muted">{{ __('ui.staff.coming') }}</p>

        @if (auth()->user()->role->canManage())
            <a class="btn" href="/admin">{{ __('ui.staff.admin') }}</a>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn secondary" type="submit">{{ __('ui.staff.logout') }}</button>
        </form>
    </div>
@endsection
