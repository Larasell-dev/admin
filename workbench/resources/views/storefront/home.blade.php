@extends('storefront::layout')

@section('title', 'Home')

@section('content')
    <h1>{{ $storefront->sales->channel }}</h1>
    <p>Every link below is the page's canonical URL in this channel and language, read with <code>Seo::availableUrl()</code>.
        Pages without one are listed without a link.</p>

    <h2>Products</h2>
    <ul class="grid">
        @foreach ($products as $product)
            @php($url = \Larasell\Larasell\Seo\Facades\Seo::availableUrl($product))
            <li>
                @if ($url)
                    <a href="{{ $url }}">{{ $product->name->get($storefront->sales->locale->value) }}</a><br>
                    <code>/{{ ltrim(substr($url, strlen(rtrim($storefront->url(), '/'))), '/') }}</code>
                @else
                    {{ $product->name->get($storefront->sales->locale->value) }}<br><code>no URL in this language</code>
                @endif
            </li>
        @endforeach
    </ul>

    <h2>Categories</h2>
    <ul class="grid">
        @foreach ($categories as $category)
            @php($url = \Larasell\Larasell\Seo\Facades\Seo::availableUrl($category))
            <li>
                @if ($url)
                    <a href="{{ $url }}">{{ $category->name->get($storefront->sales->locale->value) }}</a><br>
                    <code>/{{ ltrim(substr($url, strlen(rtrim($storefront->url(), '/'))), '/') }}</code>
                @else
                    {{ $category->name->get($storefront->sales->locale->value) }}<br><code>no URL in this language</code>
                @endif
            </li>
        @endforeach
    </ul>
@endsection
