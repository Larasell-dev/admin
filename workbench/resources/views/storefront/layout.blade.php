<!DOCTYPE html>
<html lang="{{ $storefront->sales->locale->value }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ $storefront->sales->channel }}</title>
    <style>
        body { font: 15px/1.5 system-ui, sans-serif; margin: 0; color: #18181b; background: #fafafa; }
        main { max-width: 860px; margin: 0 auto; padding: 32px 24px 64px; }
        h1 { font-size: 28px; margin: 8px 0 4px; }
        h2 { font-size: 15px; margin: 32px 0 8px; text-transform: uppercase; letter-spacing: .04em; color: #71717a; }
        a { color: #be185d; }
        .debug { background: #fff; border: 1px solid #e4e4e7; padding: 12px 16px; font-size: 13px; }
        .debug dl { display: grid; grid-template-columns: max-content 1fr; gap: 2px 16px; margin: 0; }
        .debug dt { color: #71717a; }
        .debug dd { margin: 0; font-family: ui-monospace, monospace; word-break: break-all; }
        ul.grid { list-style: none; padding: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 8px; }
        ul.grid li { background: #fff; border: 1px solid #e4e4e7; padding: 10px 12px; }
        ul.grid code, table code { font-size: 12px; color: #52525b; }
        table { border-collapse: collapse; width: 100%; background: #fff; font-size: 13px; }
        th, td { border: 1px solid #e4e4e7; padding: 6px 10px; text-align: left; }
        .badge { font-size: 11px; padding: 1px 6px; border-radius: 9px; background: #f4f4f5; }
        .canonical { background: #dcfce7; } .historic { background: #fef9c3; } .gone { background: #fee2e2; }
    </style>
</head>
<body>
<main>
    <a href="{{ $storefront->url() }}">← {{ $storefront->sales->channel }} home</a>

    <div class="debug" style="margin-top: 16px">
        <dl>
            <dt>Sales channel</dt><dd>{{ $storefront->sales->channel }} (#{{ $storefront->sales->channelId->value }})</dd>
            <dt>Language</dt><dd>{{ $storefront->sales->locale->value }}</dd>
            <dt>Currency</dt><dd>{{ $storefront->sales->currency->value }}</dd>
            <dt>Entry point</dt><dd>{{ $storefront->url() }}</dd>
            <dt>Requested URL</dt><dd>{{ request()->attributes->get(\Larasell\Larasell\Storefront\Http\Middleware\SetStorefrontContext::ORIGINAL_URI) ?? request()->getRequestUri() }}</dd>
            <dt>Other domains</dt>
            <dd>
                @foreach (\Larasell\Larasell\Storefront\Models\EntryPoint::query()->where('sales_channel_id', $storefront->sales->channelId->value)->orderBy('id')->get() as $entryPoint)
                    @php($other = $entryPoint->scheme.'://'.$entryPoint->host.($entryPoint->port ? ':'.$entryPoint->port : '').($entryPoint->prefix === '' ? '' : '/'.$entryPoint->prefix))
                    @continue(rtrim($other, '/') === rtrim($storefront->url(), '/'))
                    <a href="{{ $other }}">{{ $other }}</a> ({{ $entryPoint->locale->value }})<br>
                @endforeach
            </dd>
        </dl>
    </div>

    @yield('content')
</main>
</body>
</html>
