@extends('layouts.app')

@section('app')
    <home-component
        access-token = {!! json_decode(session('twitch_auth'))->access_token ?? '""' !!}
        client-id = {!! config('twitch.client_id') ?? '""' !!}
        favorite-streamer = {!! session('favorite_streamer') ?? '""' !!}
        >
    </home-component>
@endsection
