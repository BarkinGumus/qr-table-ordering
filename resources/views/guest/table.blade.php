@extends('layouts.app')

@section('title', $restaurant->name)

@section('content')
    <div class="card">
        <p class="muted">{{ $restaurant->name }}</p>
        <h1>{{ __('ui.guest.table') }} {{ $table->label }}</h1>
        <p class="muted">{{ __('ui.guest.soon') }}</p>
    </div>
@endsection
