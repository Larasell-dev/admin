@extends('storefront::layout')

@section('title', $title)

@section('content')
    <h1>{{ $title }}</h1>
    <p>{{ $detail }}</p>

    <h2>Every URL of this page in {{ $storefront->sales->channel }} / {{ $storefront->sales->locale->value }}</h2>
    <table>
        <tr><th>Path</th><th>Status</th><th>What opening it does</th></tr>
        @foreach ($urls as $url)
            <tr>
                <td><a href="{{ $url->url }}"><code>/{{ $url->path }}</code></a></td>
                <td><span class="badge {{ $url->status }}">{{ $url->status }}</span>@if ($url->is_custom) <span class="badge">custom</span>@endif</td>
                <td>{{ match ($url->status) { 'canonical' => 'Shows this page', 'historic' => 'Redirects (301) to the canonical path', default => 'Gone (410), or redirects to a replacement' } }}</td>
            </tr>
        @endforeach
    </table>
@endsection
