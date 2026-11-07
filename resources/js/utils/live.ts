import { echo } from "@inertiajs/react/echo";
import { configureEcho } from "@laravel/echo-react";
import { documentCookies } from "./cookies";

/** What `LiveUpdates::echoOptions()` puts in the page, for Reverb or Pusher. */
type EchoOptions = { broadcaster: "reverb" | "pusher"; key: string; authEndpoint: string } & Record<
  string,
  unknown
>;

/**
 * Connects to the app's broadcaster, so live props such as the sidebar's sales channels reload
 * when the server says they changed. Undefined when the app has no Reverb or Pusher: pages then
 * update on the next visit, as before.
 */
export function setUpLive(): ReturnType<typeof echo> | undefined {
  const config = document.getElementById("larasell-admin-live")?.textContent;

  if (!config) {
    return undefined;
  }

  const { authEndpoint, ...options } = JSON.parse(config) as EchoOptions;

  configureEcho({
    ...options,
    enabledTransports: ["ws", "wss"],
    channelAuthorization: {
      endpoint: authEndpoint,
      transport: "ajax",
      // Read per request: signing in starts a new session, and with it a new token.
      headersProvider: () => ({ "X-XSRF-TOKEN": documentCookies.get("XSRF-TOKEN") ?? "" }),
    },
  });

  return echo();
}
