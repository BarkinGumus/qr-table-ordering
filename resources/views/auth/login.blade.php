@extends('layouts.app')

@section('title', __('ui.login.title'))

@section('content')
    <div class="card">
        <h1>{{ __('ui.login.title') }}</h1>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <label for="email">{{ __('ui.login.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>

            <label for="password">{{ __('ui.login.password') }}</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            @error('email')
                <p class="error" role="alert">{{ $message }}</p>
            @enderror
            @error('password')
                <p class="error" role="alert">{{ $message }}</p>
            @enderror

            <button class="btn" type="submit">{{ __('ui.login.submit') }}</button>
        </form>
    </div>
@endsection
