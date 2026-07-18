@extends('layouts.app')

@section('title', 'FIFA World Cup 2026™ watch party - Play')

@section('content')
<div id="app">
    <player-game :admin-preview="@json($adminPreview ?? false)"></player-game>
</div>
@endsection
