import { Link, router, useHttp, usePage } from "@inertiajs/react";
import { AlertDialog, Button, Field, PasswordInput } from "@larasell-dev/ui";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import { useEffect, useState, type FormEvent } from "react";
import { assertPresent } from "ts-extras";
import type { Page } from "../types";
import { type Cookies, useCookies } from "../utils/cookies";
import { overlayExclusive } from "../utils/hotkeys";
import { useInterval } from "../utils/useInterval";
import { useOnPageVisible } from "../utils/useOnPageVisible";

/** How often to check whether the session is still alive. */
const checkInterval = 5_000;

/**
 * Laravel sets the XSRF-TOKEN cookie on every response with the session's lifetime, so it is
 * gone exactly when the session expired from inactivity.
 */
function sessionAlive(cookies: Cookies): boolean {
  return cookies.get("XSRF-TOKEN") !== undefined;
}

/**
 * Once the session expires, asks the admin for their password to sign back in without leaving
 * the page, so what they were doing, such as unsaved changes, is kept.
 */
export default function SessionExpiredDialog() {
  const { admin, loginUrl } = usePage<Page>().props;
  assertPresent(admin, "The session expired dialog needs a signed-in admin.");
  const cookies = useCookies();
  const [expired, setExpired] = useState(false);

  const check = () => {
    if (!sessionAlive(cookies)) setExpired(true);
  };
  useInterval(check, checkInterval);
  // Timers are throttled in background tabs, so check right away when the admin comes back.
  useOnPageVisible(check);

  // A visit would land on the sign-in page, or fail if it saves something, so hold it back,
  // except the dialog's own visit to the sign-in page.
  useEffect(() => {
    return router.on("before", (event) => {
      const { url, method } = event.detail.visit;
      if (
        sessionAlive(cookies) ||
        (method === "get" && url.pathname === new URL(loginUrl, location.href).pathname)
      ) {
        return;
      }
      event.preventDefault();
      setExpired(true);
    });
  }, [cookies, loginUrl]);

  return (
    // Can't be dismissed: the only ways on are signing back in or going to the sign-in page.
    <AlertDialog.Root open={expired}>
      <HotkeysProvider
        scope="session-expired-dialog"
        exclusive={overlayExclusive}
        enabled={expired}
      >
        {expired && (
          <SignInAgain
            email={admin.email}
            loginUrl={loginUrl}
            onSignedIn={() => setExpired(false)}
          />
        )}
      </HotkeysProvider>
    </AlertDialog.Root>
  );
}

function SignInAgain({
  email,
  loginUrl,
  onSignedIn,
}: {
  email: string;
  loginUrl: string;
  onSignedIn: () => void;
}) {
  const form = useHttp({ email, password: "" });
  const cookies = useCookies();

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const failed = (message = "Couldn't sign you in. Try again.") =>
      form.setError("password", message);

    try {
      // Without the cookie there is no CSRF token to sign in with; any request starts a new
      // session that hands one out.
      if (!sessionAlive(cookies)) await fetch(loginUrl, { method: "HEAD" });
    } catch {
      return failed();
    }

    // Wrong passwords come back as validation errors. Other failures reject after their callback
    // told the admin, and stay in the console.
    await form.post(loginUrl, {
      onSuccess: onSignedIn,
      onHttpException: ({ status }) =>
        failed(status === 429 ? "Too many attempts. Try again in a minute." : undefined),
      onNetworkError: () => failed(),
    });
  }

  return (
    <AlertDialog.Popup
      title="Your session expired"
      description={`You were signed out after a while without activity. Enter the password for ${email} to continue where you left off.`}
    >
      <form onSubmit={submit} className="contents">
        <Field label="Password" name="password" error={form.errors.password ?? form.errors.email}>
          <PasswordInput
            autoComplete="current-password"
            autoFocus
            value={form.data.password}
            onChange={(event) => form.setData("password", event.currentTarget.value)}
          />
        </Field>
        <AlertDialog.Actions>
          <Button variant="secondary" render={<Link href={loginUrl} />}>
            Use another account
          </Button>
          <AlertDialog.Confirm type="submit" loading={form.processing}>
            Continue
          </AlertDialog.Confirm>
        </AlertDialog.Actions>
      </form>
    </AlertDialog.Popup>
  );
}
