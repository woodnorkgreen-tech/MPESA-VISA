@extends('layouts.app')

@section('title', 'Visa Final Whistle – Play')

@section('content')
<div id="app">
    <player-game :admin-preview="@json($adminPreview ?? false)"></player-game>
</div>
@endsection
