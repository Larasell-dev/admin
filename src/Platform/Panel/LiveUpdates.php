<?php

namespace Larasell\Admin\Platform\Panel;

/**
 * How the browser connects to the app's broadcaster, so live props update without a reload. Only
 * Reverb and Pusher have a browser client; with any other broadcaster, pages update on the next
 * visit. Holds nothing secret: the key is public by design.
 */
final class LiveUpdates
{
    /** @return array<string, mixed>|null Options for Laravel Echo's configureEcho(). */
    public static function echoOptions(): ?array
    {
        $connection = config('broadcasting.connections.'.config('broadcasting.default'));
        $driver = is_array($connection) ? ($connection['driver'] ?? null) : null;

        if (! in_array($driver, ['reverb', 'pusher'], true)) {
            return null;
        }

        $options = $connection['options'] ?? [];
        $tls = ($options['scheme'] ?? 'https') === 'https';

        return array_filter([
            'broadcaster' => $driver,
            'key' => $connection['key'],
            'cluster' => $options['cluster'] ?? null,
            'wsHost' => $options['host'] ?? null,
            'wsPort' => isset($options['host']) ? (int) ($options['port'] ?? ($tls ? 443 : 80)) : null,
            'wssPort' => isset($options['host']) ? (int) ($options['port'] ?? 443) : null,
            'forceTLS' => $tls,
            'authEndpoint' => route('larasell.admin.broadcasting.auth'),
        ], fn (mixed $value): bool => $value !== null);
    }
}
