@extends('layouts.app')

@section('title', 'Game Iko On – Play')

@section('content')
<div id="app">
    <player-game :admin-preview="@json($adminPreview ?? false)"></player-game>
</div>
@endsection
